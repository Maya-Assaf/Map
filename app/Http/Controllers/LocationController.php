<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    // إضافة موقع جديد
    public function store(Request $request)
{
    $request->validate([
        'name'         => 'required|string|max:255',
        'category'     => 'required|string|max:255',
        'latitude'     => 'required|numeric',
        'longitude'    => 'required|numeric',
        'description'  => 'nullable|string',
        'images.*'     => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'references.*' => 'nullable|mimes:pdf|max:10000'
    ]);

    // Create the location including the optional description
    $location = Location::create([
        'name'        => $request->name,
        'category'    => $request->category,
        'latitude'    => $request->latitude,
        'longitude'   => $request->longitude,
        'description' => $request->description,
        'user_id'     => Auth::id()
    ]);

    // Process and store uploaded images in the public/imgs folder
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $filename = time() . '_' . $image->getClientOriginalName();
            $destinationPath = public_path('imgs');
            $image->move($destinationPath, $filename);
            // Save relative path for later use (e.g. asset($imagePath))
            $location->images()->create(['image_path' => 'imgs/' . $filename]);
        }
    }

    // Process and store uploaded PDF references in the public/refs folder
    if ($request->hasFile('references')) {
        foreach ($request->file('references') as $pdf) {
            $filename = time() . '_' . $pdf->getClientOriginalName();
            $destinationPath = public_path('refs');
            $pdf->move($destinationPath, $filename);
            $location->references()->create(['pdf_path' => 'refs/' . $filename]);
        }
    }

    // Load the associated user to get the layer information
    $location->load('user');

    return response()->json([
        'message'  => 'Location added successfully',
        'location' => [
            'id'          => $location->id,
            'name'        => $location->name,
            'category'    => $location->category,
            'latitude'    => $location->latitude,
            'longitude'   => $location->longitude,
            'description' => $location->description,
            'user_id'     => Auth::id(),
            'layer'       => $location->user->layer ?? 'unknown',
            'images'      => $location->images,      // List of image records
            'references'  => $location->references,   // List of PDF reference records

        ]
    ], 201);
}


public function index() {
    $locations = Location::with(['user', 'images', 'references'])->get();

    // Modify the data to include layer, description, images, and references
    $locations = $locations->map(function ($location) {
        return [
            'id'          => $location->id,
            'name'        => $location->name,
            'category'    => $location->category,
            'latitude'    => $location->latitude,
            'longitude'   => $location->longitude,
            'description' => $location->description,
            'layer'       => $location->user->layer ?? 'unknown',
            'images'      => $location->images,      // Returns an array of image records
            'references'  => $location->references   // Returns an array of PDF reference records
        ];
    });

    return response()->json($locations);
}


public function update(Request $request, $id)
{
    $location = Location::with(['user'])->findOrFail($id);
    $user = Auth::user();

    // Role-based authorization checks
    if ($user->position === 'Volunteer' && $location->user_id !== $user->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    if (($user->position === 'Senior leader' || $user->position === 'Junior leader') &&
        $location->user->position === 'Volunteer' &&
        $location->user->layer !== $user->layer) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Update the basic fields (remove image and reference processing)
    $data = $request->only(['name', 'category', 'description']);
    $location->update($data);

    return response()->json([
        'message'  => 'Location updated successfully',
        'location' => [
            'id'          => $location->id,
            'name'        => $location->name,
            'category'    => $location->category,
            'description' => $location->description,
            'latitude'    => $location->latitude,
            'longitude'   => $location->longitude,
            'layer'       => $location->user->layer ?? 'unknown'
        ]
    ]);
}



    // حذف موقع معين
    public function destroy($id)
    {
        $location = Location::findOrFail($id);
        $user = Auth::user();

        if ($user->position === 'Volunteer' && $location->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (($user->position === 'Senior leader' || $user->position === 'Junior leader') &&
            $location->user->position === 'Volunteer' &&
            $location->user->layer !== $user->layer) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $location->delete();
        return response()->json(['message' => 'Location deleted successfully']);
    }

    public function getLocationsByLayers(Request $request) {
        $layers = $request->input('layers', []);

        // جلب المواقع مع معلومات المستخدم
        $locations = Location::whereHas('user', function ($query) use ($layers) {
            $query->whereIn('layer', $layers);
        })->with('user')->get();

        // تعديل البيانات لإضافة `layer` الخاص بالمستخدم
        $locations = $locations->map(function ($location) {
            return [
                'id' => $location->id,
                'name' => $location->name,
                'category' => $location->category,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'layer' => $location->user->layer ?? 'unknown' // إضافة `layer` من المستخدم
            ];
        });

        return response()->json($locations);
    }


    public function uploadFiles(Request $request, $id)
    {
        $location = Location::findOrFail($id);
        $user = Auth::user();

        // Role-based authorization checks
        if ($user->position === 'Volunteer' && $location->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (($user->position === 'Senior leader' || $user->position === 'Junior leader') &&
            $location->user->position === 'Volunteer' &&
            $location->user->layer !== $user->layer) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $request->validate([
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB per image
            'references.*' => 'mimes:pdf|max:5120' // Max 5MB per PDF
        ]);

        $uploadedImages = [];
        $uploadedReferences = [];

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('imgs'), $filename);
                $imagePath = 'imgs/' . $filename;
                $location->images()->create(['image_path' => $imagePath]);
                $uploadedImages[] = $imagePath;
            }
        }

        // Handle PDF uploads
        if ($request->hasFile('references')) {
            foreach ($request->file('references') as $pdf) {
                $filename = time() . '_' . $pdf->getClientOriginalName();
                $pdf->move(public_path('refs'), $filename);
                $pdfPath = 'refs/' . $filename;
                $location->references()->create(['pdf_path' => $pdfPath]);
                $uploadedReferences[] = $pdfPath;
            }
        }

        return response()->json([
            'message' => 'Files uploaded successfully',
            'uploaded_images' => $uploadedImages,
            'uploaded_references' => $uploadedReferences
        ]);
    }

    public function deleteImage($id, $imageId)
{
    $location = Location::findOrFail($id);
    $user = Auth::user();

    // تحقق من الصلاحيات
    if ($user->position === 'Volunteer' && $location->user_id !== $user->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    if (($user->position === 'Senior leader' || $user->position === 'Junior leader') &&
        $location->user->position === 'Volunteer' &&
        $location->user->layer !== $user->layer) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // البحث عن الصورة
    $image = $location->images()->findOrFail($imageId);

    // حذف الملف الفعلي من السيرفر
    $imagePath = public_path($image->image_path);
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }

    // حذف السجل من قاعدة البيانات
    $image->delete();

    return response()->json(['message' => 'Image deleted successfully']);
}


public function deleteReference($id, $referenceId)
{
    $location = Location::findOrFail($id);
    $user = Auth::user();

    // تحقق من الصلاحيات
    if ($user->position === 'Volunteer' && $location->user_id !== $user->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    if (($user->position === 'Senior leader' || $user->position === 'Junior leader') &&
        $location->user->position === 'Volunteer' &&
        $location->user->layer !== $user->layer) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // البحث عن الملف
    $reference = $location->references()->findOrFail($referenceId);

    // حذف الملف الفعلي من السيرفر
    $referencePath = public_path($reference->pdf_path);
    if (file_exists($referencePath)) {
        unlink($referencePath);
    }

    // حذف السجل من قاعدة البيانات
    $reference->delete();

    return response()->json(['message' => 'Reference deleted successfully']);
}


public function show(Request $request, $id)
{
    // Retrieve the location along with its relationships.
    $location = Location::with(['user', 'images', 'references'])->findOrFail($id);

    return response()->json([
        'message'  => 'Location retrieved successfully',
        'location' => $location
    ]);
}


    public function exportCsv()
    {
        $locations = Location::with(['user', 'images', 'references'])->get(); // Load locations with their relationships

        $csvFileName = 'locations_export.csv';
        $headers = [
            "Content-Type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$csvFileName"
        ];

        return response()->streamDownload(function () use ($locations) {
            $handle = fopen('php://output', 'w');

            // Write BOM for UTF-8 support in Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Write headers (Columns)
            fputcsv($handle, ['ID', 'Name', 'Category', 'Latitude', 'Longitude', 'Layer', 'Description', 'Images', 'References']);

            // Write data rows
            foreach ($locations as $location) {
                // $images = $location->images->pluck('image_path')->implode(', ');
                // $references = $location->references->pluck('pdf_path')->implode(', ');
                $images = $location->images->map(function ($image) {
                    return asset($image->image_path);
                })->implode(" \n");

                // Generate full URL paths for each reference file
                $references = $location->references->map(function ($reference) {
                    return asset($reference->pdf_path);
                })->implode(" \n");


                fputcsv($handle, [
                    $location->id,
                    $location->name,
                    $location->category,
                    $location->latitude,
                    $location->longitude,
                    $location->user->layer ?? 'unknown',
                    $location->description,
                    $images,
                    $references,
                ]);
            }

            fclose($handle);
        }, $csvFileName, $headers);
    }


    public function search(Request $request)
    {

        // dd($query->toSql(), $query->getBindings());
        // Validate query parameters (optional)
        $request->validate([
            'name' => 'sometimes|string',
            'category' => 'sometimes|string',
        ]);

        // Start the query on the Location model
        $query = Location::query();

        // If a name parameter is provided, filter by name using a case-insensitive LIKE search
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // If a category parameter is provided, filter by category using a case-insensitive LIKE search
        if ($request->filled('category')) {
            $query->where('category', 'like', '%' . $request->category . '%');
        }
            // Debugging: Check generated SQL query


        // Execute the query and get the results
        $locations = $query->get();

        // Optionally, load the user relationship if you need additional user data like layer
        $locations->load('user');

        // Return the JSON response (you can customize the returned structure if needed)
        return response()->json(['locations' => $locations]);
    }



    public function Statistics()
    {
        // إجمالي عدد المواقع
        $totalLocations = Location::count();

        // عدد المواقع حسب الفئة (Category) مع ترتيب تنازلي للأكثر شيوعاً
        $locationsByCategory = Location::select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        // عدد المواقع حسب الطبقة (Layer) (باستخدام بيانات المستخدم المرتبطة)
        $locationsByLayer = Location::join('users', 'locations.user_id', '=', 'users.id')
            ->select('users.layer as layer', DB::raw('COUNT(*) as total'))
            ->groupBy('users.layer')
            ->get();

        // عدد المواقع حسب القسم (Department) (باستخدام بيانات المستخدم)
        $locationsByDepartment = Location::join('users', 'locations.user_id', '=', 'users.id')
            ->select('users.department as department', DB::raw('COUNT(*) as total'))
            ->groupBy('users.department')
            ->get();

        // عدد المواقع حسب المنصب (Position) (باستخدام بيانات المستخدم)
        $locationsByPosition = Location::join('users', 'locations.user_id', '=', 'users.id')
            ->select('users.position as position', DB::raw('COUNT(*) as total'))
            ->groupBy('users.position')
            ->get();

        // عدد المواقع المضافة شهريًا للسنة الحالية
        $locationsByMonth = Location::select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as total'))
            ->whereYear('created_at', date('Y'))
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy(DB::raw('MONTH(created_at)'))
            ->get();

        // متوسط عدد المواقع لكل مستخدم
        $avgLocationsPerUser = Location::select(DB::raw('AVG(user_count) as avg_locations'))
            ->from(function($query) {
                $query->select('user_id', DB::raw('COUNT(*) as user_count'))
                      ->from('locations')
                      ->groupBy('user_id');
            }, 'user_locations')
            ->value('avg_locations');

        // أكثر الفئات شيوعاً (مثلاً أعلى 3 فئات)
        $mostPopularCategories = $locationsByCategory->take(3);

        // أكثر المستخدمين نشاطاً (مثلاً أعلى 5 مستخدمين بإجمالي المواقع المضافة)
        $mostActiveUsers = Location::select('user_id', DB::raw('COUNT(*) as total_locations'))
            ->groupBy('user_id')
            ->orderByDesc('total_locations')
            ->limit(5)
            ->get();

        // عدد المواقع حسب النطاق الجغرافي: تقريب الإحداثيات (مثلاً تقريب latitude و longitude لمنطقة جغرافية)
        $geographicDistribution = Location::select(
                DB::raw('ROUND(latitude, 1) as lat_group'),
                DB::raw('ROUND(longitude, 1) as lng_group'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('lat_group', 'lng_group')
            ->get();

        // معدل النمو الشهري: حساب نسبة الزيادة بين كل شهر وشهر سابق
        $monthlyGrowth = [];
        $prev = null;
        foreach ($locationsByMonth as $data) {
            $month = $data->month;
            $total = $data->total;
            if (is_null($prev)) {
                $growth_rate = null; // لا يمكن حساب معدل النمو للشهر الأول
            } else {
                $growth_rate = $prev == 0 ? null : (($total - $prev) / $prev) * 100;
            }
            $monthlyGrowth[] = ['month' => $month, 'growth_rate' => $growth_rate];
            $prev = $total;
        }

        return response()->json([
            'total_locations'           => $totalLocations,
            'locations_by_category'     => $locationsByCategory,
            'locations_by_layer'        => $locationsByLayer,
            'locations_by_department'   => $locationsByDepartment,
            'locations_by_position'     => $locationsByPosition,
            'locations_by_month'        => $locationsByMonth,
            'avg_locations_per_user'    => $avgLocationsPerUser,
            'most_popular_categories'   => $mostPopularCategories,
            'most_active_users'         => $mostActiveUsers,
            'geographic_distribution'   => $geographicDistribution,
            'monthly_growth_rate'       => $monthlyGrowth,
        ]);
    }
}
