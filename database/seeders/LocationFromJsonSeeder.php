<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Aspect;
use App\Models\SubAspect;
use App\Models\Category;
use App\Models\Location;
use App\Models\LocationPoint;
use App\Models\LocationPolygon;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class LocationFromJsonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إيقاف تسجيل الاستعلامات لتوفير الذاكرة ومنع Memory Leak مع البيانات الضخمة
        DB::disableQueryLog();

        $dataDir = base_path('data');
        if (!is_dir($dataDir)) {
            $this->command->error("مجلد البيانات data غير موجود في: {$dataDir}");
            return;
        }

        // جلب أو إنشاء مستخدم لربط المواقع به
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('12345678'),
                'position' => 'Head',
                'department' => 'ADMIN DEPARTMENT',
                'layer' => 'public health',
                'is_verified' => true,
            ]);
            $this->command->info("تم إنشاء مستخدم افتراضي: {$user->email}");
        }

        // مسح جميع ملفات JSON في مجلد data وجميع مجلداته الفرعية
        $files = $this->scanAllJsonFiles($dataDir);
        $totalFiles = count($files);

        $this->command->info("تم العثور على {$totalFiles} ملف JSON داخل مجلد data ومجلداته الفرعية.");

        // تحميل بصمات المواقع الموجودة حالياً في قاعدة البيانات لمنع تكرار إدخالها
        $this->command->comment("جاري فحص المواقع المسجلة مسبقاً في قاعدة البيانات لمنع التكرار...");
        $existingLocations = $this->loadExistingLocations();
        $this->command->info("تم العثور على " . count($existingLocations) . " موقع مسجل مسبقاً. سيتم تخطي أي موقع مكرر تلقائياً.");

        // كاش في الذاكرة لتفادي تكرار استعلامات التصنيفات
        $aspectCache = [];
        $subAspectCache = [];
        $categoryCache = [];

        $totalLocationsInserted = 0;
        $totalPointsInserted = 0;
        $totalPolygonsInserted = 0;
        $totalSkipped = 0;

        $startTime = microtime(true);

        foreach ($files as $index => $filePath) {
            $relativeName = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $filePath);
            $this->command->comment("[" . ($index + 1) . "/{$totalFiles}] معالجة الملف: {$relativeName}");

            $content = file_get_contents($filePath);
            $data = json_decode($content, true);

            if (!is_array($data)) {
                $this->command->warn("تخطي الملف (ليس مصفوفة صالحة أو خطأ JSON): {$relativeName}");
                continue;
            }

            // استخراج العناصر سواء كانت GeoJSON FeatureCollection أو مصفوفة عادية
            $items = [];
            if (isset($data['type']) && $data['type'] === 'FeatureCollection' && isset($data['features']) && is_array($data['features'])) {
                foreach ($data['features'] as $feat) {
                    $props = $feat['properties'] ?? [];
                    if (isset($feat['geometry'])) {
                        $props['_geometry'] = $feat['geometry'];
                    }
                    $items[] = $props;
                }
            } else {
                $items = $data;
            }

            // تنفيذ الإدخال داخل Transaction لكل ملف لتحقيق أعلى أداء وسرعة
            DB::beginTransaction();

            try {
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    // 1. استخراج أو توليد اسم الموقع
                    $rawSub = trim((string)($item['subaspect'] ?? $item['sub_aspect'] ?? $item['sup_aspect'] ?? 'General'));
                    if ($rawSub === '') {
                        $rawSub = 'General';
                    }

                    $name = $item['name'] 
                        ?? $item['name:ar'] 
                        ?? $item['name:en'] 
                        ?? $item['official_name'] 
                        ?? $item['operator'] 
                        ?? null;

                    if (empty($name) || trim((string)$name) === '') {
                        $fallbackPart = !empty($item['amenity'])
                            ? $item['amenity']
                            : (!empty($item['shop'])
                                ? $item['shop']
                                : (!empty($item['type'])
                                    ? str_replace('_', ' ', $item['type'])
                                    : $rawSub));
                        $name = ucwords(trim($fallbackPart));
                        if (!empty($item['id'])) {
                            $name .= ' #' . $item['id'];
                        }
                    }
                    $name = mb_substr(trim((string)$name), 0, 255);

                    // 2. التحقق من الشكل الهندسي وبصمة الموقع لمنع التكرار
                    $polygonPoints = [];
                    if (!empty($item['polygon_wkt'])) {
                        $polygonPoints = $this->parseWktPolygon($item['polygon_wkt']);
                    } elseif (!empty($item['polygon']['coordinates'])) {
                        $polygonPoints = $this->parseGeoJsonPolygon($item['polygon']);
                    }

                    $isPolygon = count($polygonPoints) >= 3;
                    if ($isPolygon) {
                        $fingerprint = 'poly_' . $name . '_' . $polygonPoints[0]['latitude'] . '_' . $polygonPoints[0]['longitude'];
                    } else {
                        $lat = round((float)($item['_geometry']['coordinates'][1] ?? $item['lat'] ?? 0), 7);
                        $lon = round((float)($item['_geometry']['coordinates'][0] ?? $item['lon'] ?? 0), 7);
                        $fingerprint = 'pt_' . $name . '_' . $lat . '_' . $lon;
                    }

                    // إذا كان الموقع موجوداً بالفعل في قاعدة البيانات، نتخطاه لمنع التكرار
                    if (isset($existingLocations[$fingerprint])) {
                        $totalSkipped++;
                        continue;
                    }

                    // 3. معالجة وتحديد الـ Aspect
                    $rawAspect = trim((string)($item['aspect'] ?? 'General'));
                    if ($rawAspect === '') {
                        $rawAspect = 'General';
                    }
                    $aspectKey = mb_strtolower($rawAspect);
                    if (!isset($aspectCache[$aspectKey])) {
                        $aspectModel = Aspect::firstOrCreate(['name' => ucwords($rawAspect)]);
                        $aspectCache[$aspectKey] = $aspectModel->id;
                    }
                    $aspectId = $aspectCache[$aspectKey];

                    // 4. معالجة وتحديد الـ SubAspect
                    $subKey = $aspectId . '_' . mb_strtolower($rawSub);
                    if (!isset($subAspectCache[$subKey])) {
                        $subModel = SubAspect::firstOrCreate([
                            'name' => ucwords($rawSub),
                            'aspect_id' => $aspectId,
                        ]);
                        $subAspectCache[$subKey] = $subModel->id;
                    }
                    $subAspectId = $subAspectCache[$subKey];

                    // 5. معالجة وتحديد الـ Category
                    $rawCat = trim((string)($item['mainCategory'] ?? $item['category'] ?? $item['amenity'] ?? $item['shop'] ?? ''));
                    if ($rawCat === '' && isset($item['categories']) && is_array($item['categories']) && count($item['categories']) > 0) {
                        $rawCat = trim((string)$item['categories'][0]);
                    }
                    if ($rawCat === '' && !empty($item['type'])) {
                        $rawCat = str_replace('_', ' ', $item['type']);
                    }
                    if ($rawCat === '' && !empty($item['building']) && $item['building'] !== 'yes') {
                        $rawCat = $item['building'];
                    }
                    if ($rawCat === '' && !empty($item['highway'])) {
                        $rawCat = $item['highway'];
                    }
                    if ($rawCat === '') {
                        $rawCat = 'General';
                    }

                    $catKey = $subAspectId . '_' . mb_strtolower($rawCat);
                    if (!isset($categoryCache[$catKey])) {
                        $catModel = Category::firstOrCreate([
                            'name' => ucwords($rawCat),
                            'sub_aspect_id' => $subAspectId,
                        ]);
                        $categoryCache[$catKey] = $catModel->id;
                    }
                    $categoryId = $categoryCache[$catKey];

                    // 6. استخراج الوصف
                    $descParts = [];
                    if (!empty($item['description'])) {
                        $descParts[] = $item['description'];
                    }
                    if (!empty($item['formatted'])) {
                        $descParts[] = $item['formatted'];
                    } elseif (!empty($item['address_line1']) || !empty($item['address_line2'])) {
                        $descParts[] = trim(($item['address_line1'] ?? '') . ' ' . ($item['address_line2'] ?? ''));
                    }
                    if (!empty($item['city']) || !empty($item['region_name'])) {
                        $locStr = implode(', ', array_filter([$item['region_name'] ?? null, $item['city'] ?? null, $item['country'] ?? null]));
                        if ($locStr) {
                            $descParts[] = $locStr;
                        }
                    }
                    if (!empty($item['area_m2'])) {
                        $descParts[] = 'Area: ' . $item['area_m2'] . ' m²';
                    } elseif (!empty($item['area'])) {
                        $descParts[] = 'Area: ' . $item['area'] . ' m²';
                    }
                    if (!empty($item['perimeter_m'])) {
                        $descParts[] = 'Perimeter: ' . $item['perimeter_m'] . ' m';
                    } elseif (!empty($item['perimeter'])) {
                        $descParts[] = 'Perimeter: ' . $item['perimeter'] . ' m';
                    }
                    if (!empty($item['operator'])) {
                        $descParts[] = 'Operator: ' . $item['operator'];
                    }
                    $description = !empty($descParts) ? implode(' | ', array_unique($descParts)) : null;

                    // 7. الحفظ في قاعدة البيانات
                    if ($isPolygon) {
                        $polygon = LocationPolygon::create();
                        foreach ($polygonPoints as $pt) {
                            $polygon->points()->create($pt);
                        }
                        $polygon->locatable()->create([
                            'name' => $name,
                            'user_id' => $user->id,
                            'aspect_id' => $aspectId,
                            'sub_aspect_id' => $subAspectId,
                            'category_id' => $categoryId,
                            'description' => $description,
                        ]);
                        $totalPolygonsInserted++;
                    } else {
                        $point = LocationPoint::create([
                            'latitude' => $lat,
                            'longitude' => $lon,
                        ]);
                        $point->locatable()->create([
                            'name' => $name,
                            'user_id' => $user->id,
                            'aspect_id' => $aspectId,
                            'sub_aspect_id' => $subAspectId,
                            'category_id' => $categoryId,
                            'description' => $description,
                        ]);
                        $totalPointsInserted++;
                    }

                    // إضافة البصمة للذاكرة لمنع تكرارها ضمن نفس الملفات
                    $existingLocations[$fingerprint] = true;
                    $totalLocationsInserted++;
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->command->error("خطأ أثناء استيراد الملف {$relativeName}: " . $e->getMessage());
            }
        }

        $duration = round(microtime(true) - $startTime, 2);
        $this->command->info("==========================================");
        $this->command->info("اكتمل الفحص والاستيراد بنجاح خلال {$duration} ثانية!");
        $this->command->info("المواقع الجديدة المضافة (Newly Inserted): {$totalLocationsInserted}");
        $this->command->info("النقاط المضافة (Points): {$totalPointsInserted}");
        $this->command->info("المضلعات المضافة (Polygons): {$totalPolygonsInserted}");
        $this->command->info("المواقع التي تم تخطيها لأنها موجودة مسبقاً (Skipped Duplicates): {$totalSkipped}");
        $this->command->info("==========================================");
    }

    /**
     * جلب بصمات المواقع الموجودة حالياً في قاعدة البيانات لمنع التكرار
     */
    private function loadExistingLocations(): array
    {
        $existing = [];

        // 1. بصمات النقاط
        $points = DB::table('locations')
            ->join('location_points', function ($join) {
                $join->on('locations.locatable_id', '=', 'location_points.id')
                    ->where('locations.locatable_type', '=', 'point');
            })
            ->select('locations.name', 'location_points.latitude', 'location_points.longitude')
            ->get();

        foreach ($points as $pt) {
            $key = 'pt_' . $pt->name . '_' . round((float)$pt->latitude, 7) . '_' . round((float)$pt->longitude, 7);
            $existing[$key] = true;
        }

        // 2. بصمات المضلعات
        $polygons = DB::table('locations')
            ->join('location_polygon_points', function ($join) {
                $join->on('locations.locatable_id', '=', 'location_polygon_points.location_polygon_id')
                    ->where('locations.locatable_type', '=', 'polygon');
            })
            ->select('locations.name', 'location_polygon_points.latitude', 'location_polygon_points.longitude')
            ->get();

        foreach ($polygons as $poly) {
            $key = 'poly_' . $poly->name . '_' . round((float)$poly->latitude, 7) . '_' . round((float)$poly->longitude, 7);
            $existing[$key] = true;
        }

        return $existing;
    }

    /**
     * قراءة جميع ملفات JSON بشكل تكراري في المجلد والمجلدات الفرعية
     */
    private function scanAllJsonFiles(string $dir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'json') {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        return $files;
    }

    /**
     * استخراج نقاط المضلع من نص WKT: POLYGON((lon lat, lon lat, ...))
     */
    private function parseWktPolygon(string $wkt): array
    {
        if (!preg_match('/POLYGON\s*\(\s*\(\s*([^)]+)\s*\)\s*\)/i', $wkt, $matches)) {
            return [];
        }
        $points = [];
        $pairs = explode(',', $matches[1]);
        foreach ($pairs as $pair) {
            $coords = preg_split('/\s+/', trim($pair));
            if (count($coords) >= 2 && is_numeric($coords[0]) && is_numeric($coords[1])) {
                $points[] = [
                    'longitude' => round((float)$coords[0], 7),
                    'latitude' => round((float)$coords[1], 7),
                ];
            }
        }
        return $points;
    }

    /**
     * استخراج نقاط المضلع من كائن مضلع GeoJSON
     */
    private function parseGeoJsonPolygon(array $polygonObj): array
    {
        if (!isset($polygonObj['coordinates'][0]) || !is_array($polygonObj['coordinates'][0])) {
            return [];
        }
        $points = [];
        foreach ($polygonObj['coordinates'][0] as $coord) {
            if (is_array($coord) && count($coord) >= 2 && is_numeric($coord[0]) && is_numeric($coord[1])) {
                $points[] = [
                    'longitude' => round((float)$coord[0], 7),
                    'latitude' => round((float)$coord[1], 7),
                ];
            }
        }
        return $points;
    }
}
