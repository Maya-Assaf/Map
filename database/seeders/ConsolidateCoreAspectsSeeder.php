<?php

namespace Database\Seeders;

use App\Models\Aspect;
use App\Models\Category;
use App\Models\Location;
use App\Models\SubAspect;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsolidateCoreAspectsSeeder extends Seeder
{
    /**
     * Consolidate all non-core Aspects into the 10 official Core Aspects
     * while preserving 100% of Locations (points, paths, spaces, polygons).
     */
    public function run(): void
    {
        DB::transaction(function () {
            $totalBefore = Location::count();

            // 1. Resolve or create the 10 Core Aspects (preferring earliest ID if variants exist)
            $coreDefinitions = [
                'public_health' => [
                    'candidates' => ['Public Health'],
                    'default' => 'Public Health',
                ],
                'resources_management' => [
                    'candidates' => ['Resources Management'],
                    'default' => 'Resources Management',
                ],
                'urban_planning' => [
                    'candidates' => ['Urban Planning'],
                    'default' => 'Urban Planning',
                ],
                'building_code' => [
                    'candidates' => ['Building Code', 'Building Code & Policy'],
                    'default' => 'Building Code',
                ],
                'economy' => [
                    'candidates' => ['Economy'],
                    'default' => 'Economy',
                ],
                'ecological_factor' => [
                    'candidates' => ['Ecological Factor'],
                    'default' => 'Ecological Factor',
                ],
                'social' => [
                    'candidates' => ['Social'],
                    'default' => 'Social',
                ],
                'tech_infra' => [
                    'candidates' => ['Technology & Digital Infrastructure'],
                    'default' => 'Technology & Digital Infrastructure',
                ],
                'data_collection' => [
                    'candidates' => ['Data Collection'],
                    'default' => 'Data Collection',
                ],
                'culture' => [
                    'candidates' => ['Culture'],
                    'default' => 'Culture',
                ],
            ];

            $coreAspects = [];
            $coreAspectIds = [];

            foreach ($coreDefinitions as $key => $def) {
                $lowerCandidates = array_map('mb_strtolower', $def['candidates']);
                $aspect = Aspect::whereIn(DB::raw('LOWER(name)'), $lowerCandidates)
                    ->orderBy('id')
                    ->first();

                if (!$aspect) {
                    $aspect = Aspect::create(['name' => $def['default']]);
                }

                $coreAspects[$key] = $aspect;
                $coreAspectIds[] = $aspect->id;
            }

            // 2. Find all non-core Aspects
            $nonCoreAspects = Aspect::whereNotIn('id', $coreAspectIds)->orderBy('id')->get();

            if ($nonCoreAspects->isEmpty()) {
                if ($this->command) {
                    $this->command->info('جميع الـ Aspects مضبوطة مسبقاً على الـ 10 الأساسية فقط.');
                }
                return;
            }

            $movedLocationsTotal = 0;

            // 3. Remap every distinct (aspect_id, sub_aspect_id, category_id) group in locations
            $nonCoreIds = $nonCoreAspects->pluck('id')->all();
            $aspectsById = $nonCoreAspects->keyBy('id');

            $locationGroups = Location::whereIn('aspect_id', $nonCoreIds)
                ->select('aspect_id', 'sub_aspect_id', 'category_id', DB::raw('COUNT(*) as total'))
                ->groupBy('aspect_id', 'sub_aspect_id', 'category_id')
                ->get();

            foreach ($locationGroups as $group) {
                $oldAspect = $aspectsById->get($group->aspect_id);
                $oldSubAspect = $group->sub_aspect_id ? SubAspect::find($group->sub_aspect_id) : null;
                $oldCategory = $group->category_id ? Category::find($group->category_id) : null;

                $oldAspectName = mb_strtolower(trim($oldAspect->name ?? ''));
                $oldSubName = mb_strtolower(trim($oldSubAspect->name ?? ''));
                $oldCatName = trim($oldCategory->name ?? 'General');
                $oldCatLower = mb_strtolower($oldCatName);

                [$targetCoreKey, $subCandidates, $fallbackSubName] = $this->determineTargetMapping(
                    $oldAspectName,
                    $oldSubName,
                    $oldCatLower
                );

                $targetAspect = $coreAspects[$targetCoreKey];
                $targetSubAspect = $this->resolveSubAspect($targetAspect->id, $subCandidates, $fallbackSubName);
                $targetCategory = $this->resolveCategory($targetSubAspect->id, $oldCatName);

                $query = Location::where('aspect_id', $group->aspect_id);
                if ($group->sub_aspect_id === null) {
                    $query->whereNull('sub_aspect_id');
                } else {
                    $query->where('sub_aspect_id', $group->sub_aspect_id);
                }

                if ($group->category_id === null) {
                    $query->whereNull('category_id');
                } else {
                    $query->where('category_id', $group->category_id);
                }

                $updated = $query->update([
                    'aspect_id' => $targetAspect->id,
                    'sub_aspect_id' => $targetSubAspect->id,
                    'category_id' => $targetCategory->id,
                ]);

                $movedLocationsTotal += $updated;

                if ($this->command) {
                    $this->command->line(sprintf(
                        '  تم نقل %d موقع من [%s -> %s -> %s] إلى [%s -> %s -> %s]',
                        $updated,
                        $oldAspect->name ?? 'N/A',
                        $oldSubAspect->name ?? 'N/A',
                        $oldCatName,
                        $targetAspect->name,
                        $targetSubAspect->name,
                        $targetCategory->name
                    ));
                }
            }

            // 4. Safety check: verify 0 locations remain on non-core aspects, sub-aspects, or categories
            $remainingNonCore = Location::whereIn('aspect_id', $nonCoreIds)->count();
            if ($remainingNonCore > 0) {
                throw new \RuntimeException("فشل الأمان: لا يزال هناك {$remainingNonCore} موقع مرتبط بـ Aspect غير أساسي!");
            }

            // Also ensure no location still points to a sub_aspect belonging to a non-core aspect
            $nonCoreSubIds = SubAspect::whereIn('aspect_id', $nonCoreIds)->pluck('id')->all();
            if (!empty($nonCoreSubIds)) {
                $nonCoreCatIds = Category::whereIn('sub_aspect_id', $nonCoreSubIds)->pluck('id')->all();
                if (!empty($nonCoreCatIds)) {
                    Category::whereIn('id', $nonCoreCatIds)->delete();
                }
                SubAspect::whereIn('id', $nonCoreSubIds)->delete();
            }

            // 5. Delete the non-core Aspects
            $deletedAspectsCount = Aspect::whereIn('id', $nonCoreIds)->delete();

            // 6. Final integrity check: total locations must be 100% identical
            $totalAfter = Location::count();
            if ($totalBefore !== $totalAfter) {
                throw new \RuntimeException("خطأ في تطابق عدد المواقع: قبل ({$totalBefore}) != بعد ({$totalAfter})");
            }

            if ($this->command) {
                $this->command->info('==================================================');
                $this->command->info("تم دمج وتوحيد الـ Aspects بنجاح!");
                $this->command->info("عدد الـ Aspects المتبقية: " . Aspect::count() . " (الـ 10 الأساسية فقط)");
                $this->command->info("عدد الـ Aspects الفرعية المحذوفة بعد الدمج: {$deletedAspectsCount}");
                $this->command->info("عدد المواقع التي تم نقلها وتصحيحها: {$movedLocationsTotal}");
                $this->command->info("إجمالي المواقع المحفوظة بالكامل: {$totalAfter} (لم يُحذف أي موقع)");
                $this->command->info('==================================================');
            }
        });
    }

    /**
     * Map non-core aspect/sub-aspect/category names to the appropriate Core Aspect & SubAspect.
     */
    private function determineTargetMapping(string $aspectLower, string $subLower, string $catLower): array
    {
        // 1. Direct synonyms of the 10 Core Aspects
        if (in_array($aspectLower, ['culture & heritage', 'culture and heritage'], true)) {
            $subMap = [
                'identity' => [['Identity'], 'Identity'],
                'layers of the city' => [['Layers of the City', 'Layers Of The City'], 'Layers of the City'],
                'tangible heritage' => [['Tangible Heritage'], 'Tangible Heritage'],
                'intangible heritage' => [['Intangible Heritage'], 'Intangible Heritage'],
                'cultural hub' => [['Cultural Hub'], 'Cultural Hub'],
            ];
            [$cands, $def] = $subMap[$subLower] ?? [['Cultural Hub'], 'Cultural Hub'];
            return ['culture', $cands, $def];
        }

        if (in_array($aspectLower, ['building code & policy', 'building code'], true)) {
            $subMap = [
                'safety standards' => [['Safety Standards'], 'Safety Standards'],
                'accessibility & inclusivity' => [['Accessibility & Inclusivity'], 'Accessibility & Inclusivity'],
                'health & sanitation' => [['Health & Sanitation'], 'Health & Sanitation'],
                'structural integrity' => [['Structural Integrity'], 'Structural Integrity'],
                'ownership rights' => [['Ownership Rights'], 'Ownership Rights'],
            ];
            [$cands, $def] = $subMap[$subLower] ?? [['Safety Standards'], 'Safety Standards'];
            return ['building_code', $cands, $def];
        }

        if (in_array($aspectLower, ['economic factor', 'economy'], true)) {
            $subMap = [
                'tourism' => [['Tourism'], 'Tourism'],
                'employment development' => [['Employment Development'], 'Employment Development'],
                'local & craft industry' => [['Local & Craft Industry'], 'Local & Craft Industry'],
                'international aid' => [['International Aid'], 'International Aid'],
            ];
            [$cands, $def] = $subMap[$subLower] ?? [['Employment Development'], 'Employment Development'];
            return ['economy', $cands, $def];
        }

        if (in_array($aspectLower, ['resource management', 'resources management', 'resource mangement'], true)) {
            $subMap = [
                'water resources' => [['Water Resources'], 'Water Resources'],
                'material resources' => [['Material Resources'], 'Material Resources'],
                'food insecurity' => [['Food Insecurity', 'Food Supply Chains'], 'Food Insecurity'],
                'food supply chains' => [['Food Insecurity', 'Food Supply Chains'], 'Food Supply Chains'],
                'energy resources' => [['Energy Resources'], 'Energy Resources'],
                'capacity building' => [['Capacity Building'], 'Capacity Building'],
            ];
            [$cands, $def] = $subMap[$subLower] ?? [['Material Resources'], 'Material Resources'];
            return ['resources_management', $cands, $def];
        }

        if (in_array($aspectLower, ['social factor', 'social'], true)) {
            $subMap = [
                'education system' => [['Education System'], 'Education System'],
                'local community' => [['Local Community'], 'Local Community'],
                'civil peace' => [['Civil Peace'], 'Civil Peace'],
                'adaptation' => [['Adaptation'], 'Adaptation'],
                'community engagement' => [['Community Engagement'], 'Community Engagement'],
            ];
            [$cands, $def] = $subMap[$subLower] ?? [['Local Community'], 'Local Community'];
            return ['social', $cands, $def];
        }

        // 2. Education-related extra aspects (School, Institute, Kindergarten, Unrwa)
        if (in_array($aspectLower, ['school', 'institute', 'kindergarten', 'unrwa', 'education'], true)) {
            return ['social', ['Education System'], 'Education System'];
        }

        // 3. Health-related extra aspects
        if (in_array($aspectLower, ['health', 'health & services', 'medical'], true)) {
            return ['public_health', ['Health Care System'], 'Health Care System'];
        }

        // 4. Water / River extra aspects
        if (in_array($aspectLower, ['river', 'water'], true)) {
            return ['resources_management', ['Water Resources'], 'Water Resources'];
        }

        // 5. Ecological / Natural Environment extra aspects
        if (in_array($aspectLower, ['natural environment', 'environment', 'green'], true)) {
            return ['ecological_factor', ['Green Spaces'], 'Green Spaces'];
        }

        // 6. Food & Restaurant extra aspects
        if (in_array($aspectLower, ['food', 'restaurant'], true)) {
            return ['urban_planning', ['Amenities'], 'Amenities'];
        }

        // 7. Street & Road extra aspects
        if (in_array($aspectLower, ['street', 'road', 'transport'], true)) {
            return ['urban_planning', ['Network & Mobility', 'Transport & Connectivity'], 'Network & Mobility'];
        }

        // 8. Yard / Open Space extra aspects
        if ($aspectLower === 'yard') {
            return ['urban_planning', ['Public Spaces'], 'Public Spaces'];
        }

        // 9. Building & Housing extra aspects
        if (in_array($aspectLower, ['building', 'housing'], true)) {
            if (str_contains($catLower, 'landuse') || str_contains($catLower, 'zone')) {
                return ['urban_planning', ['Land Use'], 'Land Use'];
            }
            return ['urban_planning', ['Housing & Buildings'], 'Housing & Buildings'];
        }

        // 10. "Other" aspect (and any miscellaneous fallback) classified accurately by category
        if (str_contains($catLower, 'school') || str_contains($catLower, 'educat') || str_contains($catLower, 'kindergarten')) {
            return ['social', ['Education System'], 'Education System'];
        }
        if (str_contains($catLower, 'hospital') || str_contains($catLower, 'clinic') || str_contains($catLower, 'pharmac')) {
            return ['public_health', ['Health Care System'], 'Health Care System'];
        }
        if (str_contains($catLower, 'landuse') || str_contains($catLower, 'residential')) {
            return ['urban_planning', ['Land Use'], 'Land Use'];
        }

        // Safe default fallback into Urban Planning -> Housing & Buildings
        return ['urban_planning', ['Housing & Buildings'], 'Housing & Buildings'];
    }

    private function resolveSubAspect(int $aspectId, array $candidates, string $fallbackName): SubAspect
    {
        $lowerCandidates = array_map('mb_strtolower', $candidates);

        $sub = SubAspect::where('aspect_id', $aspectId)
            ->whereIn(DB::raw('LOWER(name)'), $lowerCandidates)
            ->orderBy('id')
            ->first();

        if (!$sub) {
            $sub = SubAspect::create([
                'name' => $fallbackName,
                'aspect_id' => $aspectId,
            ]);
        }

        return $sub;
    }

    private function resolveCategory(int $subAspectId, string $categoryName): Category
    {
        $cat = Category::where('sub_aspect_id', $subAspectId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($categoryName)])
            ->orderBy('id')
            ->first();

        if (!$cat) {
            $cat = Category::create([
                'name' => $categoryName,
                'sub_aspect_id' => $subAspectId,
            ]);
        }

        return $cat;
    }
}
