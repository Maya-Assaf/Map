<?php

namespace Database\Seeders;

use App\Http\Controllers\LocationController;
use App\Models\Aspect;
use App\Models\Category;
use App\Models\Location;
use App\Models\LocationPath;
use App\Models\LocationPoint;
use App\Models\LocationPolygon;
use App\Models\SubAspect;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixImportedLocationsGeometrySeeder extends Seeder
{
    /**
     * Fix geometry ordering, river/street paths, and category-to-marker-type alignment
     * for locations imported from JSON data files.
     *
     * Safe and idempotent: can be run multiple times on an existing or freshly seeded database.
     */
    public function run(): void
    {
        DB::disableQueryLog();
        $startTime = microtime(true);

        // 0. Ensure the 10 Core Aspects and official Categories exist and non-core Aspects are merged
        $this->call(ConsolidateCoreAspectsSeeder::class);
        $this->call(AspectSubAspectCategorySeeder::class);

        $coreAspects = $this->resolveCoreAspects();

        // 1. Group & spatially order imported Rivers and Streets into smooth LocationPath polylines
        $riverStats = $this->convertAndOrderRivers($coreAspects);
        $streetStats = $this->convertAndOrderStreets($coreAspects);

        // 2. Deduplicate closing vertices & fix vertex perimeter ordering on all Polygons and Paths
        $polyStats = $this->normalizeAllPolygons();
        $pathStats = $this->normalizeAllPaths();

        // 3. Synchronize Categories & locatable_type with LocationController::getMarkerTypeById()
        $catStats = $this->synchronizeCategoriesAndTypes($coreAspects);

        $elapsed = round(microtime(true) - $startTime, 2);

        if ($this->command) {
            $this->command->info('==========================================================');
            $this->command->info("اكتمل إصلاح وترتيب البيانات الجغرافية بنجاح خلال {$elapsed} ثانية!");
            $this->command->info("مسارات الأنهار المرتبة المنشأة (River Paths): {$riverStats['paths_created']} (دمج {$riverStats['points_merged']} نقطة)");
            $this->command->info("مسارات الشوارع المرتبة المنشأة (Street Paths): {$streetStats['paths_created']} (دمج {$streetStats['records_merged']} سجل)");
            $this->command->info("المضلعات التي تم تصحيح ترتيب رؤوسها/إزالة التكرار (Polygons Fixed): {$polyStats['polygons_fixed']} / {$polyStats['polygons_checked']}");
            $this->command->info("المسارات التي تم فك تقاطعاتها وترتيب نقاطها (Paths Fixed): {$pathStats['paths_fixed']} / {$pathStats['paths_checked']}");
            $this->command->info("المواقع التي تمت مزامنة فئاتها ونوع رسمها (Categories Synced): {$catStats['locations_remapped']}");
            $this->command->info("الفئات الخام غير الرسمية المحذوفة بعد التفريغ: {$catStats['unused_raw_categories_deleted']}");
            $this->command->info('==========================================================');
        }
    }

    /**
     * Resolve the 10 official Core Aspects.
     *
     * @return array<string, Aspect>
     */
    private function resolveCoreAspects(): array
    {
        $definitions = [
            'public_health' => ['candidates' => ['Public Health'], 'default' => 'Public Health'],
            'resources_management' => ['candidates' => ['Resources Management'], 'default' => 'Resources Management'],
            'urban_planning' => ['candidates' => ['Urban Planning'], 'default' => 'Urban Planning'],
            'building_code' => ['candidates' => ['Building Code & Policy', 'Building Code'], 'default' => 'Building Code & Policy'],
            'economy' => ['candidates' => ['Economy'], 'default' => 'Economy'],
            'ecological_factor' => ['candidates' => ['Ecological Factor'], 'default' => 'Ecological Factor'],
            'social' => ['candidates' => ['Social'], 'default' => 'Social'],
            'tech_infra' => ['candidates' => ['Technology & Digital Infrastructure'], 'default' => 'Technology & Digital Infrastructure'],
            'data_collection' => ['candidates' => ['Data Collection'], 'default' => 'Data Collection'],
            'culture' => ['candidates' => ['Culture'], 'default' => 'Culture'],
        ];

        $resolved = [];
        foreach ($definitions as $key => $def) {
            $lower = array_map('mb_strtolower', $def['candidates']);
            $aspect = Aspect::whereIn(DB::raw('LOWER(name)'), $lower)->orderBy('id')->first();
            if (!$aspect) {
                $aspect = Aspect::create(['name' => $def['default']]);
            }
            $resolved[$key] = $aspect;
        }

        return $resolved;
    }

    private function resolveSubAspect(int $aspectId, string $name, array $aliases = []): SubAspect
    {
        $candidates = array_unique(array_map('mb_strtolower', array_merge([$name], $aliases)));
        $sub = SubAspect::where('aspect_id', $aspectId)
            ->whereIn(DB::raw('LOWER(name)'), $candidates)
            ->orderBy('id')
            ->first();

        if (!$sub) {
            $sub = SubAspect::create([
                'name' => $name,
                'aspect_id' => $aspectId,
            ]);
        }

        return $sub;
    }

    private function resolveCategory(int $subAspectId, string $name): Category
    {
        $cat = Category::where('sub_aspect_id', $subAspectId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->orderBy('id')
            ->first();

        if (!$cat) {
            $cat = Category::create([
                'name' => $name,
                'sub_aspect_id' => $subAspectId,
            ]);
        }

        return $cat;
    }

    /**
     * Step 1A: Group & spatially order imported River points (e.g. نهر بردى & Waterway River)
     * into smooth, non-crossing LocationPath polylines under Urban Planning -> Infrastructure -> Water network.
     */
    private function convertAndOrderRivers(array $coreAspects): array
    {
        $riverCatIds = Category::whereIn(DB::raw('LOWER(name)'), ['waterway river', 'river'])
            ->pluck('id')
            ->all();

        $waterSourceCatIds = Category::whereRaw('LOWER(name) = ?', ['water source'])
            ->pluck('id')
            ->all();

        $rows = DB::table('locations as l')
            ->join('location_points as lp', function ($join) {
                $join->on('l.locatable_id', '=', 'lp.id')
                    ->where('l.locatable_type', '=', 'point');
            })
            ->where(function ($q) use ($riverCatIds, $waterSourceCatIds) {
                if (!empty($riverCatIds)) {
                    $q->whereIn('l.category_id', $riverCatIds);
                }
                $q->orWhere('l.name', '=', 'نهر بردى')
                    ->orWhere('l.name', 'like', 'Waterway River #%');
                if (!empty($waterSourceCatIds)) {
                    $q->orWhere(function ($sq) use ($waterSourceCatIds) {
                        $sq->whereIn('l.category_id', $waterSourceCatIds)
                            ->where(function ($nq) {
                                $nq->where('l.name', '=', 'نهر بردى')
                                    ->orWhere('l.name', 'like', 'Waterway River #%');
                            });
                    });
                }
            })
            ->select([
                'l.id as location_id',
                'l.name',
                'l.locatable_id as point_id',
                'lp.latitude',
                'lp.longitude',
            ])
            ->orderBy('l.id')
            ->get();

        if ($rows->isEmpty()) {
            return ['paths_created' => 0, 'points_merged' => 0];
        }

        $namedGroups = [];
        $unnamedPool = [];

        foreach ($rows as $row) {
            $cleanName = $this->cleanLabel((string) $row->name);
            $entry = [
                'location_id' => (int) $row->location_id,
                'point_id' => (int) $row->point_id,
                'name' => $cleanName,
                'lat' => round((float) $row->latitude, 7),
                'lon' => round((float) $row->longitude, 7),
            ];

            if ($cleanName === '' || preg_match('/^Waterway\s+River\b/i', $cleanName)) {
                $unnamedPool[] = $entry;
            } else {
                $namedGroups[$cleanName][] = $entry;
            }
        }

        // Target path taxonomy: Urban Planning -> Infrastructure -> Water network (type = path)
        $urbanAspect = $coreAspects['urban_planning'];
        $infraSub = $this->resolveSubAspect($urbanAspect->id, 'Infrastructure');
        $waterNetCat = $this->resolveCategory($infraSub->id, 'Water network');

        // Leftover point taxonomy: Resources Management -> Water Resources -> Water source (type = point)
        $resAspect = $coreAspects['resources_management'];
        $waterResSub = $this->resolveSubAspect($resAspect->id, 'Water Resources');
        $waterSourceCat = $this->resolveCategory($waterResSub->id, 'Water source');

        $pathsCreated = 0;
        $pointsMerged = 0;
        $leftoverLocationIds = [];
        $duplicateLocationIdsToDelete = [];
        $duplicatePointIdsToDelete = [];

        DB::transaction(function () use (
            $namedGroups,
            $unnamedPool,
            $urbanAspect,
            $infraSub,
            $waterNetCat,
            $resAspect,
            $waterResSub,
            $waterSourceCat,
            &$pathsCreated,
            &$pointsMerged,
            &$leftoverLocationIds,
            &$duplicateLocationIdsToDelete,
            &$duplicatePointIdsToDelete
        ) {
            foreach ($namedGroups as $riverName => $entries) {
                $extracted = $this->extractSmoothChains($entries, 900.0, -0.25, 60);
                foreach ($extracted['duplicates'] as $dup) {
                    $duplicateLocationIdsToDelete[] = $dup['location_id'];
                    $duplicatePointIdsToDelete[] = $dup['point_id'];
                }
                foreach ($extracted['chains'] as $chain) {
                    $this->persistChainAsPath(
                        $chain,
                        $riverName,
                        $urbanAspect->id,
                        $infraSub->id,
                        $waterNetCat->id
                    );
                    $pathsCreated++;
                    $pointsMerged += count($chain);
                }
                foreach ($extracted['leftovers'] as $lo) {
                    $leftoverLocationIds[] = $lo['location_id'];
                }
            }

            if (!empty($unnamedPool)) {
                $extractedUnnamed = $this->extractSmoothChains($unnamedPool, 900.0, -0.25, 60);
                foreach ($extractedUnnamed['duplicates'] as $dup) {
                    $duplicateLocationIdsToDelete[] = $dup['location_id'];
                    $duplicatePointIdsToDelete[] = $dup['point_id'];
                }
                foreach ($extractedUnnamed['chains'] as $idx => $chain) {
                    $branchLabel = 'مجرى نهر فرعي #' . $chain[0]['location_id'];
                    $this->persistChainAsPath(
                        $chain,
                        $branchLabel,
                        $urbanAspect->id,
                        $infraSub->id,
                        $waterNetCat->id
                    );
                    $pathsCreated++;
                    $pointsMerged += count($chain);
                }
                foreach ($extractedUnnamed['leftovers'] as $lo) {
                    $leftoverLocationIds[] = $lo['location_id'];
                }
            }

            if (!empty($duplicateLocationIdsToDelete)) {
                $this->chunkedDeleteByIds('locations', $duplicateLocationIdsToDelete);
            }
            if (!empty($duplicatePointIdsToDelete)) {
                $this->chunkedDeleteByIds('location_points', $duplicatePointIdsToDelete);
            }

            if (!empty($leftoverLocationIds)) {
                foreach (array_chunk(array_unique($leftoverLocationIds), 400) as $chunk) {
                    DB::table('locations')->whereIn('id', $chunk)->update([
                        'aspect_id' => $resAspect->id,
                        'sub_aspect_id' => $waterResSub->id,
                        'category_id' => $waterSourceCat->id,
                    ]);
                }
            }
        });

        return [
            'paths_created' => $pathsCreated,
            'points_merged' => $pointsMerged,
        ];
    }

    /**
     * Step 1B: Convert & spatially order imported Streets (Highway Primary & Highway Residential,
     * including synthetic 20mx20m polygons in chunk_51 and point records in chunks 42..50)
     * into smooth LocationPath polylines under Urban Planning -> Transport & Connectivity -> Classified road network.
     */
    private function convertAndOrderStreets(array $coreAspects): array
    {
        $highwayCatIds = Category::whereIn(DB::raw('LOWER(name)'), [
            'highway primary',
            'highway residential',
            'highway',
        ])->pluck('id')->all();

        // 1. Fetch point-based street records
        $pointQuery = DB::table('locations as l')
            ->join('location_points as lp', function ($join) {
                $join->on('l.locatable_id', '=', 'lp.id')
                    ->where('l.locatable_type', '=', 'point');
            })
            ->where(function ($q) use ($highwayCatIds) {
                if (!empty($highwayCatIds)) {
                    $q->whereIn('l.category_id', $highwayCatIds);
                }
                $q->orWhere('l.name', 'like', 'Highway Primary #%')
                    ->orWhere('l.name', 'like', 'Highway Residential #%');
            })
            ->select([
                'l.id as location_id',
                'l.name',
                'l.locatable_id',
                'lp.latitude',
                'lp.longitude',
            ])
            ->orderBy('l.id')
            ->get();

        // 2. Fetch synthetic polygon-based street records (from cleaned_chunk_51.json)
        $polyLocations = collect();
        if (!empty($highwayCatIds)) {
            $polyLocations = DB::table('locations as l')
                ->where('l.locatable_type', '=', 'polygon')
                ->whereIn('l.category_id', $highwayCatIds)
                ->select(['l.id as location_id', 'l.name', 'l.locatable_id'])
                ->orderBy('l.id')
                ->get();
        }

        if ($pointQuery->isEmpty() && $polyLocations->isEmpty()) {
            return ['paths_created' => 0, 'records_merged' => 0];
        }

        // Compute centroids for synthetic street polygons
        $polyCentroids = [];
        if ($polyLocations->isNotEmpty()) {
            $polyIds = $polyLocations->pluck('locatable_id')->unique()->values()->all();
            foreach (array_chunk($polyIds, 400) as $chunk) {
                $pts = DB::table('location_polygon_points')
                    ->whereIn('location_polygon_id', $chunk)
                    ->orderBy('id')
                    ->get();
                $grouped = [];
                foreach ($pts as $pt) {
                    $pid = (int) $pt->location_polygon_id;
                    $uk = round((float) $pt->latitude, 7) . '_' . round((float) $pt->longitude, 7);
                    $grouped[$pid][$uk] = ['lat' => (float) $pt->latitude, 'lon' => (float) $pt->longitude];
                }
                foreach ($grouped as $pid => $uPts) {
                    $cnt = count($uPts);
                    if ($cnt > 0) {
                        $polyCentroids[$pid] = [
                            'lat' => round(array_sum(array_column($uPts, 'lat')) / $cnt, 7),
                            'lon' => round(array_sum(array_column($uPts, 'lon')) / $cnt, 7),
                        ];
                    }
                }
            }
        }

        $namedStreets = [];
        $unnamedStreets = [];

        foreach ($pointQuery as $row) {
            $cleanName = $this->cleanLabel((string) $row->name);
            $entry = [
                'location_id' => (int) $row->location_id,
                'orig_type' => 'point',
                'point_id' => (int) $row->locatable_id,
                'polygon_id' => null,
                'name' => $cleanName,
                'lat' => round((float) $row->latitude, 7),
                'lon' => round((float) $row->longitude, 7),
            ];
            if ($cleanName === '' || preg_match('/^Highway\s+(Primary|Residential)\b/i', $cleanName)) {
                $unnamedStreets[] = $entry;
            } else {
                $namedStreets[$cleanName][] = $entry;
            }
        }

        foreach ($polyLocations as $row) {
            $pid = (int) $row->locatable_id;
            if (!isset($polyCentroids[$pid])) {
                continue;
            }
            $cleanName = $this->cleanLabel((string) $row->name);
            $entry = [
                'location_id' => (int) $row->location_id,
                'orig_type' => 'polygon',
                'point_id' => null,
                'polygon_id' => $pid,
                'name' => $cleanName,
                'lat' => $polyCentroids[$pid]['lat'],
                'lon' => $polyCentroids[$pid]['lon'],
            ];
            if ($cleanName === '' || preg_match('/^Highway\s+(Primary|Residential)\b/i', $cleanName)) {
                $unnamedStreets[] = $entry;
            } else {
                $namedStreets[$cleanName][] = $entry;
            }
        }

        $urbanAspect = $coreAspects['urban_planning'];
        $transportSub = $this->resolveSubAspect($urbanAspect->id, 'Transport & Connectivity', ['Network & Mobility']);
        $roadNetCat = $this->resolveCategory($transportSub->id, 'Classified road network');
        $transportHubCat = $this->resolveCategory($transportSub->id, 'Transport hub');

        $pathsCreated = 0;
        $recordsMerged = 0;

        DB::transaction(function () use (
            $namedStreets,
            $unnamedStreets,
            $urbanAspect,
            $transportSub,
            $roadNetCat,
            $transportHubCat,
            &$pathsCreated,
            &$recordsMerged
        ) {
            $leftovers = [];
            $duplicates = [];

            // 1. Named street chains
            foreach ($namedStreets as $streetName => $entries) {
                $extracted = $this->extractSmoothChains($entries, 1500.0, -0.35, 80);
                foreach ($extracted['duplicates'] as $dup) {
                    $duplicates[] = $dup;
                }
                foreach ($extracted['chains'] as $chain) {
                    $this->persistChainAsPath(
                        $chain,
                        $streetName,
                        $urbanAspect->id,
                        $transportSub->id,
                        $roadNetCat->id
                    );
                    $pathsCreated++;
                    $recordsMerged += count($chain);
                }
                foreach ($extracted['leftovers'] as $lo) {
                    $leftovers[] = $lo;
                }
            }

            // 2. Unnamed residential/primary road collinear chains (O(N) grid-accelerated)
            if (!empty($unnamedStreets)) {
                $extractedUnnamed = $this->extractGridCollinearChains($unnamedStreets, 180.0, 0.50, 25);
                foreach ($extractedUnnamed['duplicates'] as $dup) {
                    $duplicates[] = $dup;
                }
                foreach ($extractedUnnamed['chains'] as $chain) {
                    $roadLabel = 'مسار طريق #' . $chain[0]['location_id'];
                    $this->persistChainAsPath(
                        $chain,
                        $roadLabel,
                        $urbanAspect->id,
                        $transportSub->id,
                        $roadNetCat->id
                    );
                    $pathsCreated++;
                    $recordsMerged += count($chain);
                }
                foreach ($extractedUnnamed['leftovers'] as $lo) {
                    $leftovers[] = $lo;
                }
            }

            // Delete exact coordinate duplicates
            if (!empty($duplicates)) {
                $this->deleteMixedEntries($duplicates);
            }

            // Convert any <3 point leftovers that were synthetic polygons into real LocationPoint records,
            // and map all leftover street points to Transport hub (type = point)
            $leftoverLocIds = [];
            $polysToDelete = [];
            $now = now()->toDateTimeString();

            foreach ($leftovers as $lo) {
                $leftoverLocIds[] = $lo['location_id'];
                if (($lo['orig_type'] ?? 'point') === 'polygon' && !empty($lo['polygon_id'])) {
                    $newPointId = DB::table('location_points')->insertGetId([
                        'latitude' => $lo['lat'],
                        'longitude' => $lo['lon'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    DB::table('locations')->where('id', $lo['location_id'])->update([
                        'name' => $lo['name'] !== '' ? $lo['name'] : ('Road Node #' . $lo['location_id']),
                        'locatable_type' => 'point',
                        'locatable_id' => $newPointId,
                        'aspect_id' => $urbanAspect->id,
                        'sub_aspect_id' => $transportSub->id,
                        'category_id' => $transportHubCat->id,
                    ]);
                    $polysToDelete[] = (int) $lo['polygon_id'];
                }
            }

            if (!empty($polysToDelete)) {
                $this->chunkedDeletePolygonIds($polysToDelete);
            }

            if (!empty($leftoverLocIds)) {
                foreach (array_chunk(array_unique($leftoverLocIds), 400) as $chunk) {
                    DB::table('locations')->whereIn('id', $chunk)->update([
                        'aspect_id' => $urbanAspect->id,
                        'sub_aspect_id' => $transportSub->id,
                        'category_id' => $transportHubCat->id,
                    ]);
                }
            }
        });

        return [
            'paths_created' => $pathsCreated,
            'records_merged' => $recordsMerged,
        ];
    }

    /**
     * Persist an ordered chain of >= 3 entries as a LocationPath, keeping the first entry's Location
     * and cleaning up merged Location / LocationPoint / LocationPolygon rows.
     */
    private function persistChainAsPath(
        array $chain,
        string $name,
        int $aspectId,
        int $subAspectId,
        int $categoryId
    ): void {
        $now = now()->toDateTimeString();

        $pathId = DB::table('location_paths')->insertGetId([
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $pathPointRows = [];
        foreach ($chain as $pt) {
            $pathPointRows[] = [
                'location_path_id' => $pathId,
                'latitude' => round((float) $pt['lat'], 7),
                'longitude' => round((float) $pt['lon'], 7),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($pathPointRows, 200) as $chunk) {
            DB::table('location_path_points')->insert($chunk);
        }

        $primaryLocId = (int) $chain[0]['location_id'];
        $mergedLocIds = [];
        $oldPointIds = [];
        $oldPolygonIds = [];

        foreach ($chain as $idx => $entry) {
            if ($idx > 0) {
                $mergedLocIds[] = (int) $entry['location_id'];
            }
            if (!empty($entry['point_id'])) {
                $oldPointIds[] = (int) $entry['point_id'];
            }
            if (!empty($entry['polygon_id'])) {
                $oldPolygonIds[] = (int) $entry['polygon_id'];
            }
        }

        // Preserve any attached images or PDF references from merged locations
        if (!empty($mergedLocIds)) {
            foreach (array_chunk($mergedLocIds, 400) as $chunk) {
                DB::table('location_images')->whereIn('location_id', $chunk)->update(['location_id' => $primaryLocId]);
                DB::table('location_references')->whereIn('location_id', $chunk)->update(['location_id' => $primaryLocId]);
            }
            $this->chunkedDeleteByIds('locations', $mergedLocIds);
        }

        DB::table('locations')->where('id', $primaryLocId)->update([
            'name' => mb_substr($name, 0, 255),
            'locatable_type' => 'path',
            'locatable_id' => $pathId,
            'aspect_id' => $aspectId,
            'sub_aspect_id' => $subAspectId,
            'category_id' => $categoryId,
            'updated_at' => $now,
        ]);

        if (!empty($oldPointIds)) {
            $this->chunkedDeleteByIds('location_points', $oldPointIds);
        }
        if (!empty($oldPolygonIds)) {
            $this->chunkedDeletePolygonIds($oldPolygonIds);
        }
    }

    /**
     * Step 2A: Normalize all LocationPolygon vertices:
     *  - Remove duplicate closing vertex (p_last == p_0) & consecutive/shuffled exact duplicates
     *  - Fix self-intersecting / bowtie vertex order (sort radially around centroid + closed 2-opt)
     *  - Standardize to Counter-Clockwise (CCW) winding starting from the South-Westernmost vertex
     *  - Guarantee location_polygon_points.id strictly increases in perimeter order
     */
    private function normalizeAllPolygons(): array
    {
        $polygonIds = DB::table('location_polygons')->orderBy('id')->pluck('id')->all();
        $totalChecked = count($polygonIds);
        $totalFixed = 0;

        if ($totalChecked === 0) {
            return ['polygons_checked' => 0, 'polygons_fixed' => 0];
        }

        foreach (array_chunk($polygonIds, 400) as $idChunk) {
            $rows = DB::table('location_polygon_points')
                ->whereIn('location_polygon_id', $idChunk)
                ->orderBy('location_polygon_id')
                ->orderBy('id')
                ->get();

            $byPoly = [];
            foreach ($rows as $row) {
                $byPoly[(int) $row->location_polygon_id][] = [
                    'id' => (int) $row->id,
                    'lat' => round((float) $row->latitude, 7),
                    'lon' => round((float) $row->longitude, 7),
                ];
            }

            DB::transaction(function () use ($byPoly, &$totalFixed) {
                $idsToDelete = [];

                foreach ($byPoly as $polyId => $existingPts) {
                    if (count($existingPts) < 3) {
                        continue;
                    }

                    $normalized = self::normalizePolygonVertices($existingPts);
                    $changed = count($normalized) !== count($existingPts);

                    if (!$changed) {
                        for ($i = 0, $n = count($normalized); $i < $n; $i++) {
                            if (
                                abs($normalized[$i]['lat'] - $existingPts[$i]['lat']) > 1e-7
                                || abs($normalized[$i]['lon'] - $existingPts[$i]['lon']) > 1e-7
                            ) {
                                $changed = true;
                                break;
                            }
                        }
                    }

                    if (!$changed) {
                        continue;
                    }

                    $totalFixed++;
                    $m = count($normalized);

                    // Update first $m existing rows in-place so their IDs remain strictly ascending
                    for ($i = 0; $i < $m; $i++) {
                        if (
                            abs($normalized[$i]['lat'] - $existingPts[$i]['lat']) > 1e-7
                            || abs($normalized[$i]['lon'] - $existingPts[$i]['lon']) > 1e-7
                        ) {
                            DB::table('location_polygon_points')
                                ->where('id', $existingPts[$i]['id'])
                                ->update([
                                    'latitude' => $normalized[$i]['lat'],
                                    'longitude' => $normalized[$i]['lon'],
                                ]);
                        }
                    }

                    // Queue extra trailing rows (such as duplicate 5th closing vertex) for deletion
                    for ($i = $m, $oldCount = count($existingPts); $i < $oldCount; $i++) {
                        $idsToDelete[] = $existingPts[$i]['id'];
                    }
                }

                if (!empty($idsToDelete)) {
                    $this->chunkedDeleteByIds('location_polygon_points', $idsToDelete);
                }
            });
        }

        return [
            'polygons_checked' => $totalChecked,
            'polygons_fixed' => $totalFixed,
        ];
    }

    /**
     * Step 2B: Normalize all LocationPath points:
     *  - Remove consecutive duplicate points (keeping >= 3 points)
     *  - Untangle any self-intersecting / zig-zagging open path segments via 2-opt
     *  - Guarantee location_path_points.id strictly increases along the path
     */
    private function normalizeAllPaths(): array
    {
        $pathIds = DB::table('location_paths')->orderBy('id')->pluck('id')->all();
        $totalChecked = count($pathIds);
        $totalFixed = 0;

        if ($totalChecked === 0) {
            return ['paths_checked' => 0, 'paths_fixed' => 0];
        }

        foreach (array_chunk($pathIds, 400) as $idChunk) {
            $rows = DB::table('location_path_points')
                ->whereIn('location_path_id', $idChunk)
                ->orderBy('location_path_id')
                ->orderBy('id')
                ->get();

            $byPath = [];
            foreach ($rows as $row) {
                $byPath[(int) $row->location_path_id][] = [
                    'id' => (int) $row->id,
                    'lat' => round((float) $row->latitude, 7),
                    'lon' => round((float) $row->longitude, 7),
                ];
            }

            DB::transaction(function () use ($byPath, &$totalFixed) {
                $idsToDelete = [];

                foreach ($byPath as $pathId => $existingPts) {
                    if (count($existingPts) < 3) {
                        continue;
                    }

                    $normalized = self::normalizePathPoints($existingPts);
                    $changed = count($normalized) !== count($existingPts);

                    if (!$changed) {
                        for ($i = 0, $n = count($normalized); $i < $n; $i++) {
                            if (
                                abs($normalized[$i]['lat'] - $existingPts[$i]['lat']) > 1e-7
                                || abs($normalized[$i]['lon'] - $existingPts[$i]['lon']) > 1e-7
                            ) {
                                $changed = true;
                                break;
                            }
                        }
                    }

                    if (!$changed) {
                        continue;
                    }

                    $totalFixed++;
                    $m = count($normalized);

                    for ($i = 0; $i < $m; $i++) {
                        if (
                            abs($normalized[$i]['lat'] - $existingPts[$i]['lat']) > 1e-7
                            || abs($normalized[$i]['lon'] - $existingPts[$i]['lon']) > 1e-7
                        ) {
                            DB::table('location_path_points')
                                ->where('id', $existingPts[$i]['id'])
                                ->update([
                                    'latitude' => $normalized[$i]['lat'],
                                    'longitude' => $normalized[$i]['lon'],
                                ]);
                        }
                    }

                    for ($i = $m, $oldCount = count($existingPts); $i < $oldCount; $i++) {
                        $idsToDelete[] = $existingPts[$i]['id'];
                    }
                }

                if (!empty($idsToDelete)) {
                    $this->chunkedDeleteByIds('location_path_points', $idsToDelete);
                }
            });
        }

        return [
            'paths_checked' => $totalChecked,
            'paths_fixed' => $totalFixed,
        ];
    }

    /**
     * Step 3: Ensure every Location's Category is an official Category and its locatable_type
     * matches LocationController::getMarkerTypeById() 100%.
     */
    private function synchronizeCategoriesAndTypes(array $coreAspects): array
    {
        $officialNamesLower = array_flip(array_map('mb_strtolower', self::officialCategoryNames()));
        $controller = new LocationController();

        $groups = DB::table('locations as l')
            ->leftJoin('aspects as a', 'l.aspect_id', '=', 'a.id')
            ->leftJoin('sub_aspects as sa', 'l.sub_aspect_id', '=', 'sa.id')
            ->leftJoin('categories as c', 'l.category_id', '=', 'c.id')
            ->select([
                'l.aspect_id',
                'l.sub_aspect_id',
                'l.category_id',
                'l.locatable_type',
                'a.name as aspect_name',
                'sa.name as sub_aspect_name',
                'c.name as category_name',
                DB::raw('COUNT(*) as cnt'),
            ])
            ->groupBy(
                'l.aspect_id',
                'l.sub_aspect_id',
                'l.category_id',
                'l.locatable_type',
                'a.name',
                'sa.name',
                'c.name'
            )
            ->get();

        $locationsRemapped = 0;

        DB::transaction(function () use ($groups, $officialNamesLower, $controller, $coreAspects, &$locationsRemapped) {
            foreach ($groups as $grp) {
                $catName = trim((string) ($grp->category_name ?? ''));
                $catLower = mb_strtolower($catName);
                $locType = (string) $grp->locatable_type;

                // Space markers (circles with radius) use point-compatible categories in getMarkerTypeById
                if ($locType === 'space') {
                    continue;
                }

                $isOfficial = isset($officialNamesLower[$catLower]);
                $expectedType = $grp->category_id ? ($controller->getMarkerTypeById((int) $grp->category_id)[0] ?? 'point') : 'none';

                if ($isOfficial && $expectedType === $locType) {
                    continue;
                }

                // Special split for point schools: vocational/institutes vs general continuing education points
                if ($locType === 'point' && in_array($catLower, ['school', 'education', 'language_school', 'language school', 'poi school'], true)) {
                    $socialAspect = $coreAspects['social'];
                    $eduSub = $this->resolveSubAspect($socialAspect->id, 'Education System');
                    $vocationalCat = $this->resolveCategory($eduSub->id, 'Vocational training center');
                    $continuingCat = $this->resolveCategory($eduSub->id, 'Continuing education institution');

                    $baseQuery = DB::table('locations')
                        ->where('aspect_id', $grp->aspect_id)
                        ->where('sub_aspect_id', $grp->sub_aspect_id)
                        ->where('category_id', $grp->category_id)
                        ->where('locatable_type', 'point');

                    $vocIds = (clone $baseQuery)
                        ->where(function ($q) {
                            $q->where('name', 'like', '%معهد%')
                                ->orWhere('name', 'like', '%صناعي%')
                                ->orWhere('name', 'like', '%مهني%')
                                ->orWhere('name', 'like', '%تدريب%');
                        })
                        ->pluck('id')
                        ->all();

                    if (!empty($vocIds)) {
                        foreach (array_chunk($vocIds, 400) as $chunk) {
                            $locationsRemapped += DB::table('locations')->whereIn('id', $chunk)->update([
                                'aspect_id' => $socialAspect->id,
                                'sub_aspect_id' => $eduSub->id,
                                'category_id' => $vocationalCat->id,
                            ]);
                        }
                    }

                    $locationsRemapped += $baseQuery->update([
                        'aspect_id' => $socialAspect->id,
                        'sub_aspect_id' => $eduSub->id,
                        'category_id' => $continuingCat->id,
                    ]);

                    continue;
                }

                [$targetCoreKey, $targetSubName, $targetCatName] = $this->mapToOfficialTaxonomy(
                    mb_strtolower(trim((string) ($grp->aspect_name ?? ''))),
                    mb_strtolower(trim((string) ($grp->sub_aspect_name ?? ''))),
                    $catLower,
                    $locType
                );

                $targetAspect = $coreAspects[$targetCoreKey];
                $targetSub = $this->resolveSubAspect($targetAspect->id, $targetSubName);
                $targetCat = $this->resolveCategory($targetSub->id, $targetCatName);

                $locationsRemapped += DB::table('locations')
                    ->where('aspect_id', $grp->aspect_id)
                    ->where('sub_aspect_id', $grp->sub_aspect_id)
                    ->where('category_id', $grp->category_id)
                    ->where('locatable_type', $locType)
                    ->update([
                        'aspect_id' => $targetAspect->id,
                        'sub_aspect_id' => $targetSub->id,
                        'category_id' => $targetCat->id,
                    ]);
            }
        });

        // Clean up unused non-official raw categories that now have 0 locations referencing them
        $usedCatIds = DB::table('locations')->whereNotNull('category_id')->distinct()->pluck('category_id')->all();
        $usedLookup = array_flip($usedCatIds);

        $allCategories = Category::all(['id', 'name']);
        $rawCatIdsToDelete = [];
        foreach ($allCategories as $cat) {
            if (!isset($usedLookup[$cat->id]) && !isset($officialNamesLower[mb_strtolower(trim($cat->name))])) {
                $rawCatIdsToDelete[] = $cat->id;
            }
        }

        if (!empty($rawCatIdsToDelete)) {
            $this->chunkedDeleteByIds('categories', $rawCatIdsToDelete);
        }

        return [
            'locations_remapped' => $locationsRemapped,
            'unused_raw_categories_deleted' => count($rawCatIdsToDelete),
        ];
    }

    /**
     * Map any raw or geometry-mismatched (aspect, sub_aspect, category, locatable_type) tuple
     * to an official [core_aspect_key, SubAspectName, CategoryName] whose marker type matches $locType.
     */
    private function mapToOfficialTaxonomy(
        string $aspectLower,
        string $subLower,
        string $catLower,
        string $locType
    ): array {
        // --- 1. PATHS ---
        if ($locType === 'path') {
            if (str_contains($catLower, 'water') || str_contains($catLower, 'river')) {
                return ['urban_planning', 'Infrastructure', 'Water network'];
            }
            if (str_contains($catLower, 'telecom')) {
                return ['urban_planning', 'Infrastructure', 'Telecommunications network'];
            }
            if (str_contains($catLower, 'pedestrian') || str_contains($catLower, 'foot')) {
                return ['urban_planning', 'Transport & Connectivity', 'Pedestrian path'];
            }
            if (str_contains($catLower, 'bicycle') || str_contains($catLower, 'cycle')) {
                return ['urban_planning', 'Transport & Connectivity', 'Bicycle lane'];
            }
            if (str_contains($catLower, 'parcel') || str_contains($catLower, 'boundary')) {
                return ['building_code', 'Ownership Rights', 'Land parcel boundary'];
            }
            return ['urban_planning', 'Transport & Connectivity', 'Classified road network'];
        }

        // --- 2. POLYGONS ---
        if ($locType === 'polygon') {
            if (str_contains($catLower, 'school') || str_contains($catLower, 'educat')) {
                return ['social', 'Education System', 'School'];
            }
            if (str_contains($catLower, 'universit') || str_contains($catLower, 'college')) {
                return ['social', 'Education System', 'Universities and college'];
            }
            if (str_contains($catLower, 'landuse') || str_contains($catLower, 'residential zone')) {
                return ['urban_planning', 'Land Use', 'Residential zone'];
            }
            if (str_contains($catLower, 'garden') || str_contains($catLower, 'park')) {
                return ['urban_planning', 'Public Spaces', 'Parks and garden'];
            }
            if (str_contains($catLower, 'square') || str_contains($catLower, 'plaza')) {
                return ['urban_planning', 'Public Spaces', 'Squares and plaza'];
            }
            if (str_contains($catLower, 'historic') || str_contains($subLower, 'heritage')) {
                return ['culture', 'Tangible Heritage', 'Historical building'];
            }
            if (
                str_contains($catLower, 'restaurant')
                || str_contains($catLower, 'mall')
                || str_contains($catLower, 'market')
                || str_contains($catLower, 'commercial')
                || str_contains($catLower, 'retail')
                || str_contains($catLower, 'shop')
                || str_contains($catLower, 'fuel')
                || str_contains($catLower, 'cafe')
            ) {
                return ['urban_planning', 'Housing & Buildings', 'Commercial building'];
            }
            if (str_contains($catLower, 'hospital') || str_contains($catLower, 'clinic') || str_contains($catLower, 'government')) {
                return ['urban_planning', 'Housing & Buildings', 'Government building'];
            }
            if (str_contains($catLower, 'worship') || str_contains($catLower, 'mosque') || str_contains($catLower, 'church')) {
                return ['urban_planning', 'Housing & Buildings', 'Mixed-use building'];
            }
            if (str_contains($catLower, 'damage')) {
                return ['urban_planning', 'Housing & Buildings', 'Damaged building'];
            }
            return ['urban_planning', 'Housing & Buildings', 'Residential block'];
        }

        // --- 3. POINTS ---
        if (str_contains($catLower, 'hospital')) {
            return ['public_health', 'Health Care System', 'Hospital'];
        }
        if (str_contains($catLower, 'clinic') || str_contains($catLower, 'doctor')) {
            return ['public_health', 'Health Care System', 'Clinic'];
        }
        if (str_contains($catLower, 'pharmac')) {
            return ['public_health', 'Health Care System', 'Pharmacy'];
        }
        if (str_contains($catLower, 'fire_station') || str_contains($catLower, 'fire station') || str_contains($catLower, 'emergency')) {
            return ['public_health', 'Health Care System', 'Emergency station'];
        }
        if ($subLower === 'disease control' || str_contains($catLower, 'vaccin')) {
            return ['public_health', 'Disease Control', 'Vaccination center'];
        }
        if (str_contains($catLower, 'toilet') || str_contains($catLower, 'wc') || $subLower === 'health & sanitation') {
            return ['urban_planning', 'Amenities', 'Public WC'];
        }
        if (str_contains($catLower, 'bakery')) {
            return ['urban_planning', 'Amenities', 'Bakery'];
        }
        if (str_contains($catLower, 'fuel') || str_contains($catLower, 'gas')) {
            return ['resources_management', 'Energy Resources', 'Gas Station'];
        }
        if (str_contains($catLower, 'water') || str_contains($catLower, 'river') || $subLower === 'water resources') {
            return ['resources_management', 'Water Resources', 'Water source'];
        }
        if ($subLower === 'food supply chains' || $subLower === 'food insecurity') {
            return ['resources_management', 'Food Supply Chains', 'Food bank'];
        }
        if ($subLower === 'material resources') {
            return ['resources_management', 'Material Resources', 'Material distribution hub'];
        }
        if (str_contains($catLower, 'worship') || str_contains($catLower, 'mosque') || str_contains($catLower, 'church') || str_contains($catLower, 'religious')) {
            return ['urban_planning', 'Amenities', 'Religious center'];
        }
        if (str_contains($catLower, 'bank') || str_contains($catLower, 'financial') || str_contains($catLower, 'payment') || str_contains($catLower, 'atm')) {
            return ['economy', 'Financial & Banking System', 'Microcredit outlet'];
        }
        if (str_contains($catLower, 'mall') || str_contains($catLower, 'market') || str_contains($catLower, 'retail') || str_contains($catLower, 'kiosk') || str_contains($catLower, 'shop')) {
            if ($aspectLower === 'economy') {
                return ['economy', 'Employment Development', 'Marketplaces and market cluster'];
            }
            return ['urban_planning', 'Amenities', 'Market/mall'];
        }
        if (str_contains($catLower, 'sustenance') || str_contains($catLower, 'restaurant') || str_contains($catLower, 'cafe') || str_contains($catLower, 'food')) {
            if ($aspectLower === 'culture' || str_contains($subLower, 'heritage')) {
                return ['culture', 'Intangible Heritage', 'Traditional culinary site'];
            }
            return ['urban_planning', 'Amenities', 'Recreational amenity'];
        }
        if (str_contains($catLower, 'library')) {
            return ['culture', 'Cultural Hub', 'Public library'];
        }
        if (str_contains($catLower, 'entertainment') || str_contains($catLower, 'culture') || $aspectLower === 'culture') {
            if ($subLower === 'tourism') {
                return ['economy', 'Tourism', 'Tourist site'];
            }
            if ($subLower === 'tangible heritage') {
                return ['culture', 'Tangible Heritage', 'Archive/manuscript location'];
            }
            return ['culture', 'Cultural Hub', 'Community cultural center'];
        }
        if ($subLower === 'tourism' || str_contains($catLower, 'hotel') || str_contains($catLower, 'touris')) {
            return ['economy', 'Tourism', 'Tourist site'];
        }
        if (str_contains($catLower, 'community') || str_contains($catLower, 'conference')) {
            return ['social', 'Local Community', 'Community center'];
        }
        if (str_contains($catLower, 'post_office') || str_contains($catLower, 'post office')) {
            return ['social', 'Local Community', 'Local council office'];
        }
        if (str_contains($catLower, 'prison') || str_contains($catLower, 'police')) {
            return ['social', 'Civil Peace', 'Police/community safety post'];
        }
        if (str_contains($catLower, 'shelter')) {
            return ['building_code', 'Adaptability & Resilience', 'Emergency shelters point'];
        }
        if (str_contains($catLower, 'sport') || str_contains($catLower, 'leisure')) {
            return ['urban_planning', 'Amenities', 'Sports facility'];
        }
        if (str_contains($catLower, 'transport') || str_contains($catLower, 'highway') || str_contains($catLower, 'road')) {
            return ['urban_planning', 'Transport & Connectivity', 'Public transport stop'];
        }
        if (str_contains($catLower, 'landuse')) {
            return ['urban_planning', 'Land Use', 'Mixed Use Zone'];
        }
        if (str_contains($catLower, 'building') || $catLower === 'yes') {
            return ['building_code', 'Structural Integrity', 'Building Physical Structure'];
        }

        return ['building_code', 'Structural Integrity', 'Building Physical Structure'];
    }

    /**
     * Normalize polygon vertices:
     *  1. Deduplicate consecutive & closing duplicate points
     *  2. Deduplicate any shuffled duplicate coordinates when >= 3 unique points remain
     *  3. Untangle self-intersecting / bowtie polygons by sorting radially around centroid + closed 2-opt
     *  4. Ensure Counter-Clockwise (CCW) winding starting from the South-Westernmost vertex
     *
     * @param  array<int, array{lat?: float, lon?: float, latitude?: float, longitude?: float}>  $rawPts
     * @return array<int, array{lat: float, lon: float}>
     */
    public static function normalizePolygonVertices(array $rawPts): array
    {
        $pts = [];
        foreach ($rawPts as $p) {
            $lat = round((float) ($p['lat'] ?? $p['latitude'] ?? 0), 7);
            $lon = round((float) ($p['lon'] ?? $p['longitude'] ?? 0), 7);
            $pts[] = ['lat' => $lat, 'lon' => $lon];
        }

        if (count($pts) < 3) {
            return $pts;
        }

        // 1. Strip consecutive duplicates
        $cleaned = [$pts[0]];
        for ($i = 1, $n = count($pts); $i < $n; $i++) {
            $prev = $cleaned[count($cleaned) - 1];
            if (!self::sameCoord($prev, $pts[$i])) {
                $cleaned[] = $pts[$i];
            }
        }

        // 2. Strip closing duplicate(s) where last == first
        while (count($cleaned) > 3 && self::sameCoord($cleaned[0], $cleaned[count($cleaned) - 1])) {
            array_pop($cleaned);
        }

        // 3. Deduplicate any shuffled exact coordinate duplicates if >= 3 unique vertices remain
        $uniqueMap = [];
        $uniqueList = [];
        foreach ($cleaned as $p) {
            $key = $p['lat'] . '_' . $p['lon'];
            if (!isset($uniqueMap[$key])) {
                $uniqueMap[$key] = true;
                $uniqueList[] = $p;
            }
        }
        if (count($uniqueList) >= 3 && count($uniqueList) < count($cleaned)) {
            $cleaned = $uniqueList;
        }

        if (count($cleaned) < 3) {
            return $cleaned;
        }

        // 4. If edges self-intersect (e.g. bowtie / hourglass order), sort radially around centroid
        if (self::isPolygonSelfIntersecting($cleaned)) {
            $cleaned = self::sortVerticesAroundCentroid($cleaned);
            if (self::isPolygonSelfIntersecting($cleaned)) {
                $cleaned = self::twoOptClosedRing($cleaned);
            }
        }

        // 5. Enforce Counter-Clockwise (CCW) winding (signed area > 0 in lon/lat plane)
        if (self::signedRingArea($cleaned) < 0.0) {
            $cleaned = array_reverse($cleaned);
        }

        // 6. Rotate cyclically so vertex 0 is deterministically the South-Westernmost vertex
        $minIdx = 0;
        for ($i = 1, $n = count($cleaned); $i < $n; $i++) {
            if (
                $cleaned[$i]['lat'] < $cleaned[$minIdx]['lat'] - 1e-8
                || (
                    abs($cleaned[$i]['lat'] - $cleaned[$minIdx]['lat']) <= 1e-8
                    && $cleaned[$i]['lon'] < $cleaned[$minIdx]['lon']
                )
            ) {
                $minIdx = $i;
            }
        }

        if ($minIdx > 0) {
            $cleaned = array_merge(
                array_slice($cleaned, $minIdx),
                array_slice($cleaned, 0, $minIdx)
            );
        }

        return $cleaned;
    }

    /**
     * Normalize open path points: remove consecutive duplicates and untangle crossing segments.
     *
     * @param  array<int, array{lat?: float, lon?: float, latitude?: float, longitude?: float}>  $rawPts
     * @return array<int, array{lat: float, lon: float}>
     */
    public static function normalizePathPoints(array $rawPts): array
    {
        $pts = [];
        foreach ($rawPts as $p) {
            $lat = round((float) ($p['lat'] ?? $p['latitude'] ?? 0), 7);
            $lon = round((float) ($p['lon'] ?? $p['longitude'] ?? 0), 7);
            $pts[] = ['lat' => $lat, 'lon' => $lon];
        }

        if (count($pts) <= 3) {
            return $pts;
        }

        $cleaned = [$pts[0]];
        for ($i = 1, $n = count($pts); $i < $n; $i++) {
            if (!self::sameCoord($cleaned[count($cleaned) - 1], $pts[$i])) {
                $cleaned[] = $pts[$i];
            }
        }

        if (count($cleaned) < 3) {
            return $pts;
        }

        if (self::isOpenPathSelfIntersecting($cleaned)) {
            $cleaned = self::twoOptOpenPath($cleaned);
        }

        return $cleaned;
    }

    public static function isPolygonSelfIntersecting(array $pts): bool
    {
        $n = count($pts);
        if ($n < 4) {
            return false;
        }

        for ($i = 0; $i < $n; $i++) {
            $p1 = $pts[$i];
            $p2 = $pts[($i + 1) % $n];
            for ($j = $i + 2; $j < $n; $j++) {
                if ($i === 0 && $j === $n - 1) {
                    continue;
                }
                $p3 = $pts[$j];
                $p4 = $pts[($j + 1) % $n];
                if (self::segmentsIntersect($p1, $p2, $p3, $p4)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function isOpenPathSelfIntersecting(array $pts): bool
    {
        $n = count($pts);
        if ($n < 4) {
            return false;
        }

        for ($i = 0; $i < $n - 1; $i++) {
            $p1 = $pts[$i];
            $p2 = $pts[$i + 1];
            for ($j = $i + 2; $j < $n - 1; $j++) {
                $p3 = $pts[$j];
                $p4 = $pts[$j + 1];
                if (self::segmentsIntersect($p1, $p2, $p3, $p4)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function sortVerticesAroundCentroid(array $pts): array
    {
        $n = count($pts);
        $cx = array_sum(array_column($pts, 'lon')) / $n;
        $cy = array_sum(array_column($pts, 'lat')) / $n;
        $cosLat = max(1e-6, cos(deg2rad($cy)));

        usort($pts, function (array $a, array $b) use ($cx, $cy, $cosLat): int {
            $angA = atan2($a['lat'] - $cy, ($a['lon'] - $cx) * $cosLat);
            $angB = atan2($b['lat'] - $cy, ($b['lon'] - $cx) * $cosLat);
            if (abs($angA - $angB) > 1e-9) {
                return $angA <=> $angB;
            }
            $distA = (($a['lon'] - $cx) * $cosLat) ** 2 + ($a['lat'] - $cy) ** 2;
            $distB = (($b['lon'] - $cx) * $cosLat) ** 2 + ($b['lat'] - $cy) ** 2;
            return $distA <=> $distB;
        });

        return $pts;
    }

    private static function signedRingArea(array $pts): float
    {
        $area2 = 0.0;
        $n = count($pts);
        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $area2 += ($pts[$i]['lon'] * $pts[$j]['lat']) - ($pts[$j]['lon'] * $pts[$i]['lat']);
        }
        return 0.5 * $area2;
    }

    private static function segmentsIntersect(array $p1, array $p2, array $p3, array $p4): bool
    {
        $d1 = self::cross($p3, $p4, $p1);
        $d2 = self::cross($p3, $p4, $p2);
        $d3 = self::cross($p1, $p2, $p3);
        $d4 = self::cross($p1, $p2, $p4);

        if (
            (($d1 > 1e-15 && $d2 < -1e-15) || ($d1 < -1e-15 && $d2 > 1e-15))
            && (($d3 > 1e-15 && $d4 < -1e-15) || ($d3 < -1e-15 && $d4 > 1e-15))
        ) {
            return true;
        }

        if (abs($d1) <= 1e-15 && self::onSegment($p3, $p4, $p1)) {
            return true;
        }
        if (abs($d2) <= 1e-15 && self::onSegment($p3, $p4, $p2)) {
            return true;
        }
        if (abs($d3) <= 1e-15 && self::onSegment($p1, $p2, $p3)) {
            return true;
        }
        if (abs($d4) <= 1e-15 && self::onSegment($p1, $p2, $p4)) {
            return true;
        }

        return false;
    }

    private static function cross(array $a, array $b, array $c): float
    {
        return ($b['lon'] - $a['lon']) * ($c['lat'] - $a['lat'])
            - ($b['lat'] - $a['lat']) * ($c['lon'] - $a['lon']);
    }

    private static function onSegment(array $a, array $b, array $p): bool
    {
        return $p['lon'] >= min($a['lon'], $b['lon']) - 1e-12
            && $p['lon'] <= max($a['lon'], $b['lon']) + 1e-12
            && $p['lat'] >= min($a['lat'], $b['lat']) - 1e-12
            && $p['lat'] <= max($a['lat'], $b['lat']) + 1e-12;
    }

    private static function sameCoord(array $a, array $b): bool
    {
        return abs($a['lat'] - $b['lat']) <= 1e-7 && abs($a['lon'] - $b['lon']) <= 1e-7;
    }

    private static function twoOptClosedRing(array $pts): array
    {
        $n = count($pts);
        if ($n < 4) {
            return $pts;
        }
        $improved = true;
        $iter = 0;
        while ($improved && $iter < 40) {
            $improved = false;
            $iter++;
            for ($i = 0; $i < $n - 1; $i++) {
                $p1 = $pts[$i];
                $p2 = $pts[$i + 1];
                for ($j = $i + 2; $j < $n; $j++) {
                    if ($i === 0 && $j === $n - 1) {
                        continue;
                    }
                    $p3 = $pts[$j];
                    $p4 = $pts[($j + 1) % $n];
                    if (self::segmentsIntersect($p1, $p2, $p3, $p4)) {
                        $mid = array_reverse(array_slice($pts, $i + 1, $j - $i));
                        array_splice($pts, $i + 1, $j - $i, $mid);
                        $improved = true;
                    }
                }
            }
        }
        return $pts;
    }

    public static function twoOptOpenPath(array $pts): array
    {
        $n = count($pts);
        if ($n < 4) {
            return $pts;
        }
        $improved = true;
        $iter = 0;
        while ($improved && $iter < 40) {
            $improved = false;
            $iter++;
            for ($i = 0; $i < $n - 2; $i++) {
                for ($j = $i + 2; $j < $n - 1; $j++) {
                    $dBefore = self::distanceMeters($pts[$i], $pts[$i + 1]) + self::distanceMeters($pts[$j], $pts[$j + 1]);
                    $dAfter = self::distanceMeters($pts[$i], $pts[$j]) + self::distanceMeters($pts[$i + 1], $pts[$j + 1]);
                    if ($dAfter + 1e-4 < $dBefore || self::segmentsIntersect($pts[$i], $pts[$i + 1], $pts[$j], $pts[$j + 1])) {
                        $mid = array_reverse(array_slice($pts, $i + 1, $j - $i));
                        array_splice($pts, $i + 1, $j - $i, $mid);
                        $improved = true;
                    }
                }
            }
        }
        return $pts;
    }

    private function extractSmoothChains(
        array $entries,
        float $maxStepMeters,
        float $minCosAngle,
        int $maxPointsPerChain
    ): array {
        $unique = [];
        $duplicates = [];
        foreach ($entries as $e) {
            $k = $e['lat'] . '_' . $e['lon'];
            if (!isset($unique[$k])) {
                $unique[$k] = $e;
            } else {
                $duplicates[] = $e;
            }
        }

        $pool = array_values($unique);
        usort($pool, fn(array $a, array $b) => $a['lon'] <=> $b['lon'] ?: $a['lat'] <=> $b['lat']);

        $n = count($pool);
        $used = array_fill(0, $n, false);
        $chains = [];
        $leftovers = [];

        for ($seed = 0; $seed < $n; $seed++) {
            if ($used[$seed]) {
                continue;
            }
            $used[$seed] = true;
            $chain = [$pool[$seed]];

            // Grow forward from tail
            while (count($chain) < $maxPointsPerChain) {
                $m = count($chain);
                $curr = $chain[$m - 1];
                $prev = $m >= 2 ? $chain[$m - 2] : null;
                $bestIdx = -1;
                $bestScore = INF;

                for ($i = 0; $i < $n; $i++) {
                    if ($used[$i]) {
                        continue;
                    }
                    $d = self::distanceMeters($curr, $pool[$i]);
                    if ($d > $maxStepMeters) {
                        continue;
                    }
                    if ($prev !== null) {
                        $ca = self::cosTurnAngle($prev, $curr, $pool[$i]);
                        if ($ca < $minCosAngle) {
                            continue;
                        }
                        $score = $d * (1.5 - 0.5 * $ca);
                    } else {
                        $score = $d;
                    }
                    if ($score < $bestScore) {
                        $bestScore = $score;
                        $bestIdx = $i;
                    }
                }

                if ($bestIdx === -1) {
                    break;
                }
                $used[$bestIdx] = true;
                $chain[] = $pool[$bestIdx];
            }

            // Grow backward from head
            while (count($chain) < $maxPointsPerChain) {
                $m = count($chain);
                $curr = $chain[0];
                $nextInChain = $m >= 2 ? $chain[1] : null;
                $bestIdx = -1;
                $bestScore = INF;

                for ($i = 0; $i < $n; $i++) {
                    if ($used[$i]) {
                        continue;
                    }
                    $d = self::distanceMeters($curr, $pool[$i]);
                    if ($d > $maxStepMeters) {
                        continue;
                    }
                    if ($nextInChain !== null) {
                        $ca = self::cosTurnAngle($nextInChain, $curr, $pool[$i]);
                        if ($ca < $minCosAngle) {
                            continue;
                        }
                        $score = $d * (1.5 - 0.5 * $ca);
                    } else {
                        $score = $d;
                    }
                    if ($score < $bestScore) {
                        $bestScore = $score;
                        $bestIdx = $i;
                    }
                }

                if ($bestIdx === -1) {
                    break;
                }
                $used[$bestIdx] = true;
                array_unshift($chain, $pool[$bestIdx]);
            }

            if (count($chain) >= 3) {
                $chains[] = self::twoOptOpenPath($chain);
            } else {
                foreach ($chain as $lo) {
                    $leftovers[] = $lo;
                }
            }
        }

        return [
            'chains' => $chains,
            'leftovers' => $leftovers,
            'duplicates' => $duplicates,
        ];
    }

    /**
     * O(N) spatial-grid accelerated chain extractor for large pools of unnamed road nodes.
     */
    private function extractGridCollinearChains(
        array $entries,
        float $maxStepMeters,
        float $minCosAngle,
        int $maxPointsPerChain
    ): array {
        $unique = [];
        $duplicates = [];
        foreach ($entries as $e) {
            $k = $e['lat'] . '_' . $e['lon'];
            if (!isset($unique[$k])) {
                $unique[$k] = $e;
            } else {
                $duplicates[] = $e;
            }
        }

        $pool = array_values($unique);
        usort($pool, fn(array $a, array $b) => $a['lon'] <=> $b['lon'] ?: $a['lat'] <=> $b['lat']);

        $cellDeg = max(0.001, ($maxStepMeters / 111320.0) * 1.25);
        $grid = [];
        foreach ($pool as $idx => $p) {
            $gx = (int) floor($p['lon'] / $cellDeg);
            $gy = (int) floor($p['lat'] / $cellDeg);
            $grid["{$gx}_{$gy}"][] = $idx;
        }

        $n = count($pool);
        $used = array_fill(0, $n, false);
        $chains = [];
        $leftovers = [];

        for ($seed = 0; $seed < $n; $seed++) {
            if ($used[$seed]) {
                continue;
            }
            $used[$seed] = true;
            $chain = [$pool[$seed]];

            while (count($chain) < $maxPointsPerChain) {
                $m = count($chain);
                $curr = $chain[$m - 1];
                $prev = $m >= 2 ? $chain[$m - 2] : null;
                $gx = (int) floor($curr['lon'] / $cellDeg);
                $gy = (int) floor($curr['lat'] / $cellDeg);
                $bestIdx = -1;
                $bestScore = INF;

                for ($dx = -1; $dx <= 1; $dx++) {
                    for ($dy = -1; $dy <= 1; $dy++) {
                        foreach ($grid[($gx + $dx) . '_' . ($gy + $dy)] ?? [] as $candIdx) {
                            if ($used[$candIdx]) {
                                continue;
                            }
                            $cand = $pool[$candIdx];
                            $d = self::distanceMeters($curr, $cand);
                            if ($d > $maxStepMeters) {
                                continue;
                            }
                            if ($prev !== null) {
                                $ca = self::cosTurnAngle($prev, $curr, $cand);
                                if ($ca < $minCosAngle) {
                                    continue;
                                }
                                $score = $d * (1.5 - 0.5 * $ca);
                            } else {
                                $score = $d;
                            }
                            if ($score < $bestScore) {
                                $bestScore = $score;
                                $bestIdx = $candIdx;
                            }
                        }
                    }
                }

                if ($bestIdx === -1) {
                    break;
                }
                $used[$bestIdx] = true;
                $chain[] = $pool[$bestIdx];
            }

            if (count($chain) >= 3) {
                $chains[] = self::twoOptOpenPath($chain);
            } else {
                foreach ($chain as $lo) {
                    $leftovers[] = $lo;
                }
            }
        }

        return [
            'chains' => $chains,
            'leftovers' => $leftovers,
            'duplicates' => $duplicates,
        ];
    }

    private static function distanceMeters(array $a, array $b): float
    {
        $R = 6371000.0;
        $dLat = deg2rad($b['lat'] - $a['lat']);
        $dLon = deg2rad($b['lon'] - $a['lon']);
        $x = sin($dLat * 0.5) ** 2
            + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * (sin($dLon * 0.5) ** 2);
        return 2.0 * $R * atan2(sqrt($x), sqrt(max(0.0, 1.0 - $x)));
    }

    private static function cosTurnAngle(array $pPrev, array $pCurr, array $pNext): float
    {
        $cosLat = cos(deg2rad($pCurr['lat']));
        $v1x = ($pCurr['lon'] - $pPrev['lon']) * $cosLat;
        $v1y = $pCurr['lat'] - $pPrev['lat'];
        $v2x = ($pNext['lon'] - $pCurr['lon']) * $cosLat;
        $v2y = $pNext['lat'] - $pCurr['lat'];
        $n1 = hypot($v1x, $v1y);
        $n2 = hypot($v2x, $v2y);
        if ($n1 < 1e-12 || $n2 < 1e-12) {
            return 1.0;
        }
        return ($v1x * $v2x + $v1y * $v2y) / ($n1 * $n2);
    }

    private function cleanLabel(string $label): string
    {
        $cleaned = preg_replace('/[\x{200E}\x{200F}\x{202A}-\x{202E}]/u', '', $label) ?? $label;
        return trim($cleaned);
    }

    private function deleteMixedEntries(array $entries): void
    {
        $locIds = [];
        $ptIds = [];
        $polyIds = [];
        foreach ($entries as $e) {
            $locIds[] = (int) $e['location_id'];
            if (!empty($e['point_id'])) {
                $ptIds[] = (int) $e['point_id'];
            }
            if (!empty($e['polygon_id'])) {
                $polyIds[] = (int) $e['polygon_id'];
            }
        }
        if (!empty($locIds)) {
            $this->chunkedDeleteByIds('locations', $locIds);
        }
        if (!empty($ptIds)) {
            $this->chunkedDeleteByIds('location_points', $ptIds);
        }
        if (!empty($polyIds)) {
            $this->chunkedDeletePolygonIds($polyIds);
        }
    }

    private function chunkedDeletePolygonIds(array $polygonIds): void
    {
        $unique = array_values(array_unique($polygonIds));
        foreach (array_chunk($unique, 400) as $chunk) {
            DB::table('location_polygon_points')->whereIn('location_polygon_id', $chunk)->delete();
            DB::table('location_polygons')->whereIn('id', $chunk)->delete();
        }
    }

    private function chunkedDeleteByIds(string $table, array $ids): void
    {
        $unique = array_values(array_unique($ids));
        foreach (array_chunk($unique, 400) as $chunk) {
            DB::table($table)->whereIn('id', $chunk)->delete();
        }
    }

    /**
     * Complete list of official Category names defined in LocationSeeder & AspectSubAspectCategorySeeder.
     *
     * @return array<int, string>
     */
    public static function officialCategoryNames(): array
    {
        return [
            // 1. Public Health
            'Hospital', 'Health Center', 'Clinic', 'Pharmacy', 'Emergency station',
            'Physical therapy center', 'Prosthetics clinic', 'Disability support center', 'Mobile rehab team',
            'Vaccination center',
            'Nutritional counseling center', 'Emergency kitchens',
            'Medicine distribution point', 'Drug warehouse',
            'Mental health center', 'Psychological counseling service', 'Addiction treatment center',
            // 2. Resources Management
            'Training & development center', 'Research & training hub', 'Temporary training hub',
            'Water source', 'Water distribution node', 'Water storage reservoir', 'Emergency water point',
            'Food bank', 'Humanitarian Food distribution hub',
            'Construction material market', 'Recycling facility', 'Material distribution hub', 'Rubble staging area',
            'Power generation plant', 'Energy transmission and distribution node', 'Renewable energy installation',
            'Energy storage facility', 'Emergency generator', 'Gas Station',
            // 3. Urban Planning
            'Squares and plaza', 'Parks and garden', 'Playground', 'Multi-use open space', 'Designated safe gathering point',
            'Sports facility', 'Religious center', 'Recreational amenity', 'Cultural center', 'Health facility',
            'Market/mall', 'Public WC', 'Bakery',
            'Residential block', 'Commercial building', 'Industrial site', 'Government building',
            'Educational building', 'Mixed-use building', 'Informal building', 'Damaged building',
            'Informal settlement',
            'Residential zone', 'Commercial zone', 'Industrial zone', 'Agricultural zone',
            'Recreational zone', 'Protected area', 'Reconstruction site', 'Mixed Use Zone',
            'Water network', 'Sewage main', 'Electricity substation', 'Telecommunications network',
            'Waste transfer station', 'Dams and bridge', 'Damaged infrastructure',
            'Major ongoing project', 'Ongoing Major project',
            'Classified road network', 'Public transport stop', 'Bicycle lane', 'Pedestrian path',
            'Transport hub', 'Sidewalks and walkway', 'Parking area', 'Blocked/impassable segment',
            'Temporary repairs/bridges',
            // 4. Data Collection
            'Statistical Office', 'Monitoring station', 'Data center',
            // 5. Technology & Digital Infrastructure
            'Community digital hub', 'Public internet access point',
            'E-learning facility', 'E-government service center',
            'Mobile networks tower', 'Public Wi-Fi hotspot', 'Temporary connectivity points',
            // 6. Ecological Factor
            'Tree Canopy', 'Public garden', 'Urban forest', 'Green belt', 'Green roofs and wall',
            'Ecological corridor', 'Private garden',
            'Waste collection point', 'Waste sorting station', 'Landfills location', 'Emergency waste collection point',
            'Air quality monitoring station', 'Water quality monitoring station', 'Emergency water testing point',
            'Disaster Designated shelter',
            'Agricultural land', 'Irrigation system', 'Greenhouse', 'Emergency seed/food depots',
            // 7. Social
            'Registered peace building programs office', 'Police/community safety post',
            'Migrant reception center', 'Language/translation service', 'Legal assistance center',
            'Migrant support hub', 'Entry monitoring point',
            'Local council office', 'Community center', 'Public event venue', 'Volunteer coordination hub',
            'Psychosocial support center', 'Community resilience program office', 'Rehabilitation programs site',
            'School', 'Universities and college', 'Vocational training center',
            'Continuing education institution', 'Educational library', 'Research center', 'Temporary learning space',
            'Participatory planning office', 'Public consultation venue',
            'Community development projects office', 'Complaint/feedback kiosk',
            // 8. Culture
            'Museums', 'Community cultural center', 'Theaters and cinema', 'Public library',
            'Art gallery', 'Traditional handicraft center', 'Temporary cultural/relief exhibition space',
            'Monument', 'Craft center', 'Library/gallery',
            'Old city polygon', 'Historical neighborhood polygon', 'Sequential urban expansion polygon',
            'Old industrial area polygon', 'Modern development polygon',
            'Historical building', 'Archaeological site', 'Museum repository',
            'Archive/manuscript location', 'Damaged heritage site',
            'Traditional culinary site',
            // 9. Building Code & Policy
            'Property registry office', 'Land parcel boundary', 'Leasing office',
            'Fire alarm presence', 'Emergency exits location', 'Building monitoring center',
            'Assessed/damaged building polygon', 'Damaged/partially standing/collapse/rubble polygon',
            'Building Physical Structure',
            'Buildings with insulation', 'Energy efficient HVAC system', 'Recycling facilitys for construction material',
            'Ramps and elevator', 'Accessible restroom', 'Braille signage and audio signal',
            'Wheelchair-accessible paths and space', 'Accessible transport station',
            'Water treatment plant', 'Waste disposal facility', 'Sewage connection point', 'Emergency sanitation point',
            'Climate-resilient building', 'Emergency shelters point', 'Multi-used/convertible facility',
            'Temporary shelter cluster',
            // 10. Economy
            'Craft workshop', 'Handicraft center', 'Artisan market', 'Traditional production cluster',
            'Tool/equipment workshop', 'Craft training center', 'Temporary/household workshop',
            'Active international project', 'Humanitarian coordination hub', 'NGO office location',
            'Reconstruction programs site', 'Temporary distribution point',
            'Business incubator', 'Employment center', 'Marketplaces and market cluster',
            'Tech hub', 'Creative industries space',
            'Tourist site', 'Hotel', 'Accommodation unit', 'Cultural tourism node',
            'Visitor center', 'Temporarily closed site',
            'Microcredit outlet', 'Social protection office', 'Cash assistance distribution point',
            'Industrial cluster', 'Factory', 'Manufacturing Workshop',
        ];
    }
}
