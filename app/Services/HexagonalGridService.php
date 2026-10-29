<?php

namespace App\Services;

use App\Http\Resources\NearbyLocationResource;
use App\Models\Location;
use App\Models\LocationPath;
use App\Models\LocationPoint;
use App\Models\LocationPolygon;
use App\Models\LocationSpace;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use PDO;

class HexagonalGridService
{
    public const KM_PER_DEG_LAT = 111.32;

    /**
     * Get nearby locations partitioned into a hexagonal grid with sorting and pagination.
     *
     * Uses a two-phase late-materialization pipeline:
     *  - Phase 1 streams lightweight indexed coordinates via PDO::FETCH_NUM, assigns hex rings/distances
     *    in flat scalar arrays, and sorts in native C (array_multisort).
     *  - Phase 2 hydrates Eloquent models and relationships strictly for the current page slice.
     */
    public function getNearbyLocations(array $validated, Request $request): array
    {
        $originLat = (float) $validated['latitude'];
        $originLng = (float) $validated['longitude'];
        $hexSizeKm = (float) ($validated['hex_size_km'] ?? 1.0);
        $rings = (int) ($validated['rings'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);

        // 1. Generate the hexagonal grid (k-ring around the target point)
        $hexagons = $this->generateKRingGrid($originLat, $originLng, $hexSizeKm, $rings);

        // 2. Compute bounding box to pre-filter locations in SQL using spatial indexes
        $bbox = $this->getGridBoundingBox($originLat, $originLng, $hexSizeKm, $rings);
        $spatialQuery = $this->buildSpatialCandidateQuery(
            $validated,
            $bbox['min_lat'],
            $bbox['max_lat'],
            $bbox['min_lng'],
            $bbox['max_lng']
        );

        // 3. Stream lightweight coordinate rows and map candidates to hexagons without hydrating Eloquent models
        $kmPerDegLng = $this->getKmPerDegLng($originLat);
        $twoThirds = 2.0 / 3.0;
        $negOneThird = -1.0 / 3.0;
        $sqrt3Over3 = sqrt(3.0) / 3.0;
        $cosOriginLat = cos(deg2rad($originLat));

        $bestRings = [];
        $bestDists = [];
        $bestQ = [];
        $bestR = [];
        $bestLat = [];
        $bestLng = [];
        $bestPtOrder = [];

        $connection = DB::connection();
        if (!$connection->pretending()) {
            $sql = $spatialQuery->toSql();
            $bindings = $connection->prepareBindings($spatialQuery->getBindings());
            $startQuery = microtime(true);

            $stmt = $connection->getPdo()->prepare($sql);
            $stmt->execute($bindings);

            if ($connection->logging()) {
                $connection->logQuery($sql, $bindings, round((microtime(true) - $startQuery) * 1000, 2));
            }

            while (($row = $stmt->fetch(PDO::FETCH_NUM)) !== false) {
                $locId = (int) $row[0];
                $lat = (float) $row[1];
                $lng = (float) $row[2];
                $ptOrder = (int) $row[3];

                $xKm = ($lng - $originLng) * $kmPerDegLng;
                $yKm = ($lat - $originLat) * self::KM_PER_DEG_LAT;

                $qFrac = ($twoThirds * $xKm) / $hexSizeKm;
                $rFrac = ($negOneThird * $xKm + $sqrt3Over3 * $yKm) / $hexSizeKm;
                $sFrac = -$qFrac - $rFrac;

                $q = (int) round($qFrac);
                $r = (int) round($rFrac);
                $s = (int) round($sFrac);

                $qDiff = abs($q - $qFrac);
                $rDiff = abs($r - $rFrac);
                $sDiff = abs($s - $sFrac);

                if ($qDiff > $rDiff && $qDiff > $sDiff) {
                    $q = -$r - $s;
                } elseif ($rDiff > $sDiff) {
                    $r = -$q - $s;
                } else {
                    $s = -$q - $r;
                }

                $ring = max(abs($q), abs($r), abs($s));
                if ($ring > $rings) {
                    continue;
                }

                // Skip Haversine calculation if this multi-vertex location already has a strictly closer ring
                if (isset($bestRings[$locId]) && $ring > $bestRings[$locId]) {
                    continue;
                }

                $dLat = deg2rad($lat - $originLat);
                $dLng = deg2rad($lng - $originLng);
                $sinHalfLat = sin($dLat * 0.5);
                $sinHalfLng = sin($dLng * 0.5);
                $a = $sinHalfLat * $sinHalfLat
                    + $cosOriginLat * cos(deg2rad($lat)) * $sinHalfLng * $sinHalfLng;
                $dist = round(6371.0 * 2.0 * atan2(sqrt($a), sqrt(max(0.0, 1.0 - $a))), 4);

                if (
                    !isset($bestRings[$locId])
                    || $ring < $bestRings[$locId]
                    || ($ring === $bestRings[$locId] && (
                        $dist < $bestDists[$locId]
                        || ($dist === $bestDists[$locId] && $ptOrder < $bestPtOrder[$locId])
                    ))
                ) {
                    $bestRings[$locId] = $ring;
                    $bestDists[$locId] = $dist;
                    $bestQ[$locId] = $q;
                    $bestR[$locId] = $r;
                    $bestLat[$locId] = $lat;
                    $bestLng[$locId] = $lng;
                    $bestPtOrder[$locId] = $ptOrder;
                }
            }
        }

        // Tally marker counts per hexagon and count occupied hexagons
        foreach ($bestQ as $locId => $q) {
            $hexId = "hex_q{$q}_r{$bestR[$locId]}";
            if (isset($hexagons[$hexId])) {
                $hexagons[$hexId]['markers_count']++;
            }
        }

        $occupiedHexagonsCount = 0;
        foreach ($hexagons as $hex) {
            if ($hex['markers_count'] > 0) {
                $occupiedHexagonsCount++;
            }
        }

        // 4. Sort by hexagon ring first, then exact Haversine distance, then ID (in native C)
        $totalMatched = count($bestRings);
        $pageIds = [];

        if ($totalMatched > 0) {
            $idsList = array_keys($bestRings);
            $ringsList = array_values($bestRings);
            $distsList = array_values($bestDists);

            array_multisort(
                $ringsList, SORT_ASC, SORT_NUMERIC,
                $distsList, SORT_ASC, SORT_NUMERIC,
                $idsList, SORT_ASC, SORT_NUMERIC
            );

            $offset = ($page - 1) * $perPage;
            if ($offset < $totalMatched) {
                $pageIds = array_slice($idsList, $offset, $perPage);
            }
        }

        // 5. Deferred Hydration: Load full Eloquent models & relations ONLY for the paginated slice
        $paginatedItems = collect();

        if (!empty($pageIds)) {
            $locationsById = Location::query()
                ->leftJoin('users', 'locations.user_id', '=', 'users.id')
                ->leftJoin('aspects', 'locations.aspect_id', '=', 'aspects.id')
                ->leftJoin('sub_aspects', 'locations.sub_aspect_id', '=', 'sub_aspects.id')
                ->leftJoin('categories', 'locations.category_id', '=', 'categories.id')
                ->select([
                    'locations.*',
                    'users.layer as joined_user_layer',
                    'aspects.name as joined_aspect_name',
                    'sub_aspects.name as joined_sub_aspect_name',
                    'categories.name as joined_category_name',
                ])
                ->with(['images', 'references', 'locatable'])
                ->whereIn('locations.id', $pageIds)
                ->get()
                ->keyBy('id');

            foreach ($pageIds as $locId) {
                $location = $locationsById->get($locId);
                if (!$location) {
                    continue;
                }

                $q = $bestQ[$locId];
                $r = $bestR[$locId];
                $hexId = "hex_q{$q}_r{$r}";
                $gridHex = $hexagons[$hexId] ?? null;

                $hexInfo = $gridHex !== null ? [
                    'hex_id' => $gridHex['hex_id'],
                    'q' => $gridHex['q'],
                    'r' => $gridHex['r'],
                    's' => $gridHex['s'],
                    'ring' => $gridHex['ring'],
                    'center' => $gridHex['center'],
                ] : [
                    'hex_id' => $hexId,
                    'q' => $q,
                    'r' => $r,
                    's' => -$q - $r,
                    'ring' => $bestRings[$locId],
                    'center' => $this->hexToLatLng($q, $r, $originLat, $originLng, $hexSizeKm),
                ];

                $paginatedItems->push([
                    'id' => $location->id,
                    'name' => $location->name,
                    'description' => $location->description,
                    'aspect_id' => $location->aspect_id,
                    'aspect' => $location->joined_aspect_name ?? 'unknown',
                    'sub_aspect_id' => $location->sub_aspect_id,
                    'sub_aspect' => $location->joined_sub_aspect_name ?? 'unknown',
                    'category_id' => $location->category_id,
                    'category' => $location->joined_category_name ?? 'unknown',
                    'user_id' => $location->user_id,
                    'user_layer' => $location->joined_user_layer ?? 'unknown',
                    'locatable_type' => $location->locatable_type,
                    'locatable' => $location->locatable,
                    'images' => $location->images,
                    'references' => $location->references,
                    'representative_point' => [
                        'latitude' => $bestLat[$locId],
                        'longitude' => $bestLng[$locId],
                    ],
                    'distance_km' => $bestDists[$locId],
                    'hexagon' => $hexInfo,
                    'created_at' => $location->created_at,
                    'updated_at' => $location->updated_at,
                ]);
            }
        }

        $paginator = new LengthAwarePaginator(
            $paginatedItems,
            $totalMatched,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $transformedData = NearbyLocationResource::collection($paginatedItems)->resolve();
        $paginatorArray = $paginator->toArray();
        $paginatorArray['data'] = $transformedData;

        return [
            'grid_metadata' => [
                'query_point' => [
                    'latitude' => $originLat,
                    'longitude' => $originLng,
                ],
                'hex_size_km' => $hexSizeKm,
                'rings' => $rings,
                'total_hexagons_in_grid' => count($hexagons),
                'occupied_hexagons_count' => $occupiedHexagonsCount,
                'center_hexagon' => $hexagons[$this->formatHexId(0, 0)] ?? null,
                'hexagons' => array_values($hexagons),
            ],
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ],
            'locations' => $paginatorArray,
        ];
    }

    /**
     * Build an index-driven lightweight spatial candidate query across requested locatable geometry tables.
     */
    private function buildSpatialCandidateQuery(
        array $validated,
        float $minLat,
        float $maxLat,
        float $minLng,
        float $maxLng
    ): Builder {
        $requestedType = !empty($validated['type']) ? strtolower($validated['type']) : null;

        $applyFilters = function (Builder $query) use ($validated): void {
            if (!empty($validated['aspect_id'])) {
                $query->where('l.aspect_id', (int) $validated['aspect_id']);
            }
            if (!empty($validated['sub_aspect_id'])) {
                $query->where('l.sub_aspect_id', (int) $validated['sub_aspect_id']);
            }
            if (!empty($validated['category_id'])) {
                $query->where('l.category_id', (int) $validated['category_id']);
            }
        };

        $branches = [];

        if ($requestedType === null || $requestedType === 'point') {
            $pointMorph = (new LocationPoint())->getMorphClass();
            $q = DB::table('location_points as lp')
                ->join('locations as l', function ($join) use ($pointMorph) {
                    $join->on('l.locatable_id', '=', 'lp.id')
                        ->where('l.locatable_type', '=', $pointMorph);
                })
                ->whereBetween('lp.latitude', [$minLat, $maxLat])
                ->whereBetween('lp.longitude', [$minLng, $maxLng])
                ->selectRaw('l.id as location_id, lp.latitude as lat, lp.longitude as lng, 0 as pt_order');
            $applyFilters($q);
            $branches[] = $q;
        }

        if ($requestedType === null || $requestedType === 'space') {
            $spaceMorph = (new LocationSpace())->getMorphClass();
            $q = DB::table('location_spaces as ls')
                ->join('locations as l', function ($join) use ($spaceMorph) {
                    $join->on('l.locatable_id', '=', 'ls.id')
                        ->where('l.locatable_type', '=', $spaceMorph);
                })
                ->whereBetween('ls.latitude', [$minLat, $maxLat])
                ->whereBetween('ls.longitude', [$minLng, $maxLng])
                ->selectRaw('l.id as location_id, ls.latitude as lat, ls.longitude as lng, 0 as pt_order');
            $applyFilters($q);
            $branches[] = $q;
        }

        if ($requestedType === null || $requestedType === 'path') {
            $pathMorph = (new LocationPath())->getMorphClass();
            $q = DB::table('location_path_points as lpp')
                ->join('location_paths as lpth', 'lpth.id', '=', 'lpp.location_path_id')
                ->join('locations as l', function ($join) use ($pathMorph) {
                    $join->on('l.locatable_id', '=', 'lpth.id')
                        ->where('l.locatable_type', '=', $pathMorph);
                })
                ->whereBetween('lpp.latitude', [$minLat, $maxLat])
                ->whereBetween('lpp.longitude', [$minLng, $maxLng])
                ->selectRaw('l.id as location_id, lpp.latitude as lat, lpp.longitude as lng, lpp.id as pt_order');
            $applyFilters($q);
            $branches[] = $q;
        }

        if ($requestedType === null || $requestedType === 'polygon') {
            $polygonMorph = (new LocationPolygon())->getMorphClass();
            $q = DB::table('location_polygon_points as lpolyp')
                ->join('location_polygons as lpoly', 'lpoly.id', '=', 'lpolyp.location_polygon_id')
                ->join('locations as l', function ($join) use ($polygonMorph) {
                    $join->on('l.locatable_id', '=', 'lpoly.id')
                        ->where('l.locatable_type', '=', $polygonMorph);
                })
                ->whereBetween('lpolyp.latitude', [$minLat, $maxLat])
                ->whereBetween('lpolyp.longitude', [$minLng, $maxLng])
                ->selectRaw('l.id as location_id, lpolyp.latitude as lat, lpolyp.longitude as lng, lpolyp.id as pt_order');
            $applyFilters($q);
            $branches[] = $q;
        }

        $combined = array_shift($branches);
        foreach ($branches as $branch) {
            $combined->unionAll($branch);
        }

        return $combined;
    }

    /**
     * Convert a geographic coordinate (latitude, longitude) to axial/cube hexagon coordinates (q, r, s)
     * relative to the grid origin (originLat, originLng) and hexagon size (radius/side length in km).
     */
    public function latLngToHex(
        float $lat,
        float $lng,
        float $originLat,
        float $originLng,
        float $hexSizeKm = 1.0
    ): array {
        $kmPerDegLng = $this->getKmPerDegLng($originLat);

        $xKm = ($lng - $originLng) * $kmPerDegLng;
        $yKm = ($lat - $originLat) * self::KM_PER_DEG_LAT;

        // Flat-top hexagon fractional axial coordinates
        $qFrac = ((2.0 / 3.0) * $xKm) / $hexSizeKm;
        $rFrac = ((-1.0 / 3.0) * $xKm + (sqrt(3.0) / 3.0) * $yKm) / $hexSizeKm;

        $axial = $this->axialRound($qFrac, $rFrac);
        $ring = $this->hexDistance($axial['q'], $axial['r'], 0, 0);
        $center = $this->hexToLatLng($axial['q'], $axial['r'], $originLat, $originLng, $hexSizeKm);

        return [
            'hex_id' => $this->formatHexId($axial['q'], $axial['r']),
            'q' => $axial['q'],
            'r' => $axial['r'],
            's' => $axial['s'],
            'ring' => $ring,
            'center' => $center,
        ];
    }

    /**
     * Convert axial coordinates (q, r) back to center geographic coordinates (latitude, longitude).
     */
    public function hexToLatLng(
        int $q,
        int $r,
        float $originLat,
        float $originLng,
        float $hexSizeKm = 1.0
    ): array {
        $kmPerDegLng = $this->getKmPerDegLng($originLat);

        $xCenterKm = $hexSizeKm * 1.5 * $q;
        $yCenterKm = $hexSizeKm * sqrt(3.0) * ($r + 0.5 * $q);

        return [
            'latitude' => round($originLat + ($yCenterKm / self::KM_PER_DEG_LAT), 7),
            'longitude' => round($originLng + ($xCenterKm / $kmPerDegLng), 7),
        ];
    }

    /**
     * Compute the 6 polygon boundary vertices for a hexagon (q, r).
     */
    public function getHexVertices(
        int $q,
        int $r,
        float $originLat,
        float $originLng,
        float $hexSizeKm = 1.0
    ): array {
        $kmPerDegLng = $this->getKmPerDegLng($originLat);

        $xCenterKm = $hexSizeKm * 1.5 * $q;
        $yCenterKm = $hexSizeKm * sqrt(3.0) * ($r + 0.5 * $q);

        $vertices = [];
        for ($i = 0; $i < 6; $i++) {
            $angleRad = deg2rad(60 * $i);
            $vxKm = $xCenterKm + $hexSizeKm * cos($angleRad);
            $vyKm = $yCenterKm + $hexSizeKm * sin($angleRad);

            $vertices[] = [
                'latitude' => round($originLat + ($vyKm / self::KM_PER_DEG_LAT), 7),
                'longitude' => round($originLng + ($vxKm / $kmPerDegLng), 7),
            ];
        }

        return $vertices;
    }

    /**
     * Generate all hexagons within k rings around the origin hexagon (0, 0).
     * Total hexagons = 1 + 3 * k * (k + 1).
     */
    public function generateKRingGrid(
        float $originLat,
        float $originLng,
        float $hexSizeKm = 1.0,
        int $rings = 1
    ): array {
        $kmPerDegLng = $this->getKmPerDegLng($originLat);
        $sqrt3 = sqrt(3.0);

        $vertexOffsetsKm = [];
        for ($i = 0; $i < 6; $i++) {
            $angleRad = deg2rad(60 * $i);
            $vertexOffsetsKm[] = [
                $hexSizeKm * cos($angleRad),
                $hexSizeKm * sin($angleRad),
            ];
        }

        $hexagons = [];

        for ($q = -$rings; $q <= $rings; $q++) {
            $rMin = max(-$rings, -$q - $rings);
            $rMax = min($rings, -$q + $rings);
            $xCenterKm = $hexSizeKm * 1.5 * $q;

            for ($r = $rMin; $r <= $rMax; $r++) {
                $s = -$q - $r;
                $ring = max(abs($q), abs($r), abs($s));
                $hexId = "hex_q{$q}_r{$r}";
                $yCenterKm = $hexSizeKm * $sqrt3 * ($r + 0.5 * $q);

                $vertices = [];
                foreach ($vertexOffsetsKm as [$dxKm, $dyKm]) {
                    $vertices[] = [
                        'latitude' => round($originLat + (($yCenterKm + $dyKm) / self::KM_PER_DEG_LAT), 7),
                        'longitude' => round($originLng + (($xCenterKm + $dxKm) / $kmPerDegLng), 7),
                    ];
                }

                $hexagons[$hexId] = [
                    'hex_id' => $hexId,
                    'q' => $q,
                    'r' => $r,
                    's' => $s,
                    'ring' => $ring,
                    'center' => [
                        'latitude' => round($originLat + ($yCenterKm / self::KM_PER_DEG_LAT), 7),
                        'longitude' => round($originLng + ($xCenterKm / $kmPerDegLng), 7),
                    ],
                    'vertices' => $vertices,
                    'markers_count' => 0,
                ];
            }
        }

        uasort($hexagons, function (array $a, array $b) {
            if ($a['ring'] !== $b['ring']) {
                return $a['ring'] <=> $b['ring'];
            }
            if ($a['q'] !== $b['q']) {
                return $a['q'] <=> $b['q'];
            }
            return $a['r'] <=> $b['r'];
        });

        return $hexagons;
    }

    /**
     * Compute bounding box [min_lat, max_lat, min_lng, max_lng] that encloses the entire k-ring hex grid.
     */
    public function getGridBoundingBox(
        float $originLat,
        float $originLng,
        float $hexSizeKm = 1.0,
        int $rings = 1
    ): array {
        $kmPerDegLng = $this->getKmPerDegLng($originLat);

        // Outer distance covering all hexagon vertices in the k-ring plus safety margin
        $maxDistanceKm = $hexSizeKm * (sqrt(3.0) * ($rings + 0.5) + 1.0);

        $deltaLat = $maxDistanceKm / self::KM_PER_DEG_LAT;
        $deltaLng = $maxDistanceKm / $kmPerDegLng;

        return [
            'min_lat' => max(-90.0, $originLat - $deltaLat),
            'max_lat' => min(90.0, $originLat + $deltaLat),
            'min_lng' => max(-180.0, $originLng - $deltaLng),
            'max_lng' => min(180.0, $originLng + $deltaLng),
        ];
    }

    /**
     * Calculate exact Haversine distance in kilometers between two coordinates.
     */
    public function haversineDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(max(0.0, 1.0 - $a)));

        return round($earthRadiusKm * $c, 4);
    }

    /**
     * Calculate hexagonal grid step distance between two axial coordinates.
     */
    public function hexDistance(int $q1, int $r1, int $q2 = 0, int $r2 = 0): int
    {
        $dq = $q1 - $q2;
        $dr = $r1 - $r2;
        $ds = -$dq - $dr;

        return (int) max(abs($dq), abs($dr), abs($ds));
    }

    /**
     * Cube rounding algorithm to snap fractional axial coordinates to the nearest hexagon.
     */
    public function axialRound(float $qFrac, float $rFrac): array
    {
        $sFrac = -$qFrac - $rFrac;

        $q = (int) round($qFrac);
        $r = (int) round($rFrac);
        $s = (int) round($sFrac);

        $qDiff = abs($q - $qFrac);
        $rDiff = abs($r - $rFrac);
        $sDiff = abs($s - $sFrac);

        if ($qDiff > $rDiff && $qDiff > $sDiff) {
            $q = -$r - $s;
        } elseif ($rDiff > $sDiff) {
            $r = -$q - $s;
        } else {
            $s = -$q - $r;
        }

        // Normalize -0 to 0
        $q = $q === 0 ? 0 : $q;
        $r = $r === 0 ? 0 : $r;
        $s = $s === 0 ? 0 : $s;

        return [
            'q' => $q,
            'r' => $r,
            's' => $s,
        ];
    }

    public function formatHexId(int $q, int $r): string
    {
        return "hex_q{$q}_r{$r}";
    }

    private function getKmPerDegLng(float $lat): float
    {
        return max(1e-6, self::KM_PER_DEG_LAT * cos(deg2rad($lat)));
    }
}
