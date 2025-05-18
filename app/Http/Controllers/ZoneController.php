<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Zone;

class ZoneController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //Get all the zones
    public function index()
    {
        //
        // $zones = Zone::all();
        // return response()->json($zones);
        $zones = Zone::with(['user', 'ZoneImages', 'ZoneReferences'])->get();

        $zones = $zones->map(function ($zone) {
            return [
                'id'          => $zone->id,
                'name'        => $zone->name,
                'category'    => $zone->category,
                'latitude'    => $zone->latitude,
                'longitude'   => $zone->longitude,
                'radius'      => $zone->radius,
                'layer'       => $zone->user->layer ?? 'unknown',
                'ZoneImages'      => $zone->images,     
                'ZoneReferences'  => $zone->references   
            ];
        });
    
        return response()->json($zones);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        // Authorize the action using the ZonePolicy
        $this->authorize('create', Zone::class);
        // Validate the request data
        $request->validate([
            'name'         => 'required|string|max:255',
            'category'     => 'required|string|max:255',
            'latitude'     => 'required|numeric',
            'longitude'    => 'required|numeric',
            'radius'       => 'required|numeric',
            'images.*'     => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'references.*' => 'nullable|mimes:pdf|max:10000'
        ]);

        // Get the authenticated user
        $user = Auth::user();

        // Create the zone including the optional description
        $zone = Zone::create([
            'name'        => $request->name,
            'category'    => $request->category,
            'latitude'    => $request->latitude,
            'longitude'   => $request->longitude,
            'radius'      => $request->radius,
            'user_id'     => $user->id, // Use the authenticated user's ID
            'layer'       => $user->layer, // Add the user's layer directly
        ]);

        // Process and store uploaded images in the public/imgs folder
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = time() . '_' . $image->getClientOriginalName();
                $destinationPath = public_path('imgs');
                $image->move($destinationPath, $filename);
                // Save relative path for later use (e.g. asset($imagePath))
                $zone->ZoneImages()->create(['image_path' => 'imgs/' . $filename]);
            }
        }

    // Process and store uploaded PDF references in the public/refs folder
    if ($request->hasFile('references')) {
        foreach ($request->file('references') as $pdf) {
            $filename = time() . '_' . $pdf->getClientOriginalName();
            $destinationPath = public_path('refs');
            $pdf->move($destinationPath, $filename);
            $zone->ZoneReferences()->create(['pdf_path' => 'refs/' . $filename]);
        }
    }
    
    return response()->json([
        'message'  => 'Zone added successfully',
        'zone' => [
            'id'          => $zone->id,
            'name'        => $zone->name,
            'category'    => $zone->category,
            'latitude'    => $zone->latitude,
            'longitude'   => $zone->longitude,
            'radius'      => $zone->radius,
            'user_id'     => $user->id,
            'layer'       => $user->layer, // Add the user's layer directly
            'images'      => $zone->images,      // List of image records
            'references'  => $zone->references,   // List of PDF reference records
        ]
    ], 201);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        //
        $zone = Zone::with(['user', 'ZoneImages', 'ZoneReferences'])->findOrFail($id);

        return response()->json([
            'message'  => 'Zone retrieved successfully',
            'zone' => $zone
        ]);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
            //
            // Find the zone
            $zone = Zone::with(['user'])->findOrFail($id);

            // Authorize the action using the ZonePolicy
            $this->authorize('update', $zone);

            // Get the authenticated user
            $user = Auth::user();

            // Validate the request data
            $request->validate([
                'name'         => 'sometimes|string|max:255',
                'category'     => 'sometimes|string|max:255',
                'latitude'     => 'sometimes|numeric',
                'longitude'    => 'sometimes|numeric',
                'radius'       => 'sometimes|numeric',
            ]);

            // Update the zone
            $zone->update($request->only(['name', 'category', 'latitude', 'longitude', 'radius']));

            
            return response()->json([
                'message'  => 'Zone updated successfully',
                'zone' => [
                    'id'          => $zone->id,
                    'name'        => $zone->name,
                    'category'    => $zone->category,
                    'latitude'    => $zone->latitude,
                    'longitude'   => $zone->longitude,
                    'radius'      => $zone->radius,
                    'layer'       => $user->layer, // Use the authenticated user's layer directly
                ]
            ]);
     }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        // Find the zone
        $zone = Zone::findOrFail($id);

        // Authorize the action using the ZonePolicy
        $this->authorize('delete', $zone);

        // Delete the zone
        $zone->delete();

        return response()->json(['message' => 'Zone deleted successfully']);

    }


    public function getZonesByLayers(Request $request)
{
        // Get the layers from the request (default to an empty array if not provided)
        $layers = $request->input('layers', []);

        // Fetch zones with user information where the user's layer is in the provided layers
        $zones = Zone::whereHas('user', function ($query) use ($layers) {
            $query->whereIn('layer', $layers);
        })->with('user')->get();

        // Modify the data to include the user's `layer`
        $zones = $zones->map(function ($zone) {
            return [
                'id'          => $zone->id,
                'name'        => $zone->name,
                'category'    => $zone->category,
                'latitude'    => $zone->latitude,
                'longitude'   => $zone->longitude,
                'radius'      => $zone->radius,
                'layer'       => $zone->user->layer ?? 'unknown', // Add `layer` from the user
                'user_id'     => $zone->user_id,
            ];
        });

        return response()->json($zones);
    }
}
