<?php

namespace App\Services;

use App\Http\Resources\NearbyLocationResource;
use App\Models\Location;
use App\Models\LocationPath;
use App\Models\LocationPoint;
use App\Models\LocationPolygon;
use App\Models\LocationSpace;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class HexagonalGridService
{
    public const KM_PER_DEG_LAT = 111.32;

    /**
     * Get nearby locations partitioned into a hexagonal grid with sorting and pagination.
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

        // 2. Compute bounding box to pre-filter locations in SQL
        $bbox = $this->getGridBoundingBox($originLat, $originLng, $hexSizeKm, $rings);
        $minLat = $bbox['min_lat'];
        $maxLat = $bbox['max_lat'];
        $minLng = $bbox['min_lng'];
        $maxLng = $bbox['max_lng'];

        $query = Location::with(['user', 'images', 'references', 'aspect', 'subAspect', 'category', 'locatable']);

        if (!empty($validated['aspect_id'])) {
            $query->where('aspect_id', $validated['aspect_id']);
        }
        if (!empty($validated['sub_aspect_id'])) {
            $query->where('sub_aspect_id', $validated['sub_aspect_id']);
        }
        if (!empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }
        if (!empty($validated['type'])) {
            $typeMap = [
                'point' => LocationPoint::class,
                'path' => LocationPath::class,
                'space' => LocationSpace::class,
                'polygon' => LocationPolygon::class,
            ];
            $query->where('locatable_type', $typeMap[strtolower($validated['type'])]);
        }

        $query->where(function ($q) use ($minLat, $maxLat, $minLng, $maxLng) {
            $q->whereHasMorph('locatable', [LocationPoint::class, LocationSpace::class], function ($morphQuery) use ($minLat, $maxLat, $minLng, $maxLng) {
                $morphQuery->whereBetween('latitude', [$minLat, $maxLat])
                    ->whereBetween('longitude', [$minLng, $maxLng]);
            })
            ->orWhereHasMorph('locatable', [LocationPath::class], function ($morphQuery) use ($minLat, $maxLat, $minLng, $maxLng) {
                $morphQuery->whereHas('points', function ($pointQuery) use ($minLat, $maxLat, $minLng, $maxLng) {
                    $pointQuery->whereBetween('latitude', [$minLat, $maxLat])
                        ->whereBetween('longitude', [$minLng, $maxLng]);
                });
            })
            ->orWhereHasMorph('locatable', [LocationPolygon::class], function ($morphQuery) use ($minLat, $maxLat, $minLng, $maxLng) {
                $morphQuery->whereHas('points', function ($pointQuery) use ($minLat, $maxLat, $minLng, $maxLng) {
                    $pointQuery->whereBetween('latitude', [$minLat, $maxLat])
                        ->whereBetween('longitude', [$minLng, $maxLng]);
                });
            });
        });

        $candidateLocations = $query->get();

        // 3. Map each candidate location to its hexagon in the grid and filter by exact hex ring
        $matchedLocations = collect();

        foreach ($candidateLocations as $location) {
            $locatable = $location->locatable;
            if (!$locatable) {
                continue;
            }

            $bestHex = null;
            $bestDistanceKm = null;
            $representativePoint = null;

            if ($locatable instanceof LocationPoint || $locatable instanceof LocationSpace) {
                $lat = (float) $locatable->latitude;
                $lng = (float) $locatable->longitude;
                $bestHex = $this->latLngToHex($lat, $lng, $originLat, $originLng, $hexSizeKm);
                $bestDistanceKm = $this->haversineDistanceKm($originLat, $originLng, $lat, $lng);
                $representativePoint = ['latitude' => $lat, 'longitude' => $lng];
            } elseif ($locatable instanceof LocationPath || $locatable instanceof LocationPolygon) {
                foreach ($locatable->points as $pt) {
                    $lat = (float) $pt->latitude;
                    $lng = (float) $pt->longitude;
                    $hex = $this->latLngToHex($lat, $lng, $originLat, $originLng, $hexSizeKm);
                    $dist = $this->haversineDistanceKm($originLat, $originLng, $lat, $lng);

                    if (
                        $bestHex === null ||
                        $hex['ring'] < $bestHex['ring'] ||
                        ($hex['ring'] === $bestHex['ring'] && $dist < $bestDistanceKm)
                    ) {
                        $bestHex = $hex;
                        $bestDistanceKm = $dist;
                        $representativePoint = ['latitude' => $lat, 'longitude' => $lng];
                    }
                }
            }

            if ($bestHex === null || $bestHex['ring'] > $rings) {
                continue;
            }

            $hexId = $bestHex['hex_id'];
            if (isset($hexagons[$hexId])) {
                $hexagons[$hexId]['markers_count']++;
            }

            $matchedLocations->push([
                'id' => $location->id,
                'name' => $location->name,
                'description' => $location->description,
                'aspect_id' => $location->aspect_id,
                'aspect' => optional($location->aspect)->name ?? 'unknown',
                'sub_aspect_id' => $location->sub_aspect_id,
                'sub_aspect' => optional($location->subAspect)->name ?? 'unknown',
                'category_id' => $location->category_id,
                'category' => optional($location->category)->name ?? 'unknown',
                'user_id' => $location->user_id,
                'user_layer' => optional($location->user)->layer ?? 'unknown',
                'locatable_type' => $location->locatable_type,
                'locatable' => $location->locatable,
                'images' => $location->images,
                'references' => $location->references,
                'representative_point' => $representativePoint,
                'distance_km' => $bestDistanceKm,
                'hexagon' => $bestHex,
                'created_at' => $location->created_at,
                'updated_at' => $location->updated_at,
            ]);
        }

        // 4. Sort by hexagon ring first, then exact Haversine distance, then ID
        $sortedLocations = $matchedLocations->sort(function (array $a, array $b) {
            if ($a['hexagon']['ring'] !== $b['hexagon']['ring']) {
                return $a['hexagon']['ring'] <=> $b['hexagon']['ring'];
            }
            if ($a['distance_km'] !== $b['distance_km']) {
                return $a['distance_km'] <=> $b['distance_km'];
            }
            return $a['id'] <=> $b['id'];
        })->values();

        // 5. Paginate results
        $paginatedItems = $sortedLocations->forPage($page, $perPage)->values();
        $paginator = new LengthAwarePaginator(
            $paginatedItems,
            $sortedLocations->count(),
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
                'occupied_hexagons_count' => collect($hexagons)->where('markers_count', '>', 0)->count(),
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
        $hexagons = [];

        for ($q = -$rings; $q <= $rings; $q++) {
            $rMin = max(-$rings, -$q - $rings);
            $rMax = min($rings, -$q + $rings);

            for ($r = $rMin; $r <= $rMax; $r++) {
                $s = -$q - $r;
                $ring = $this->hexDistance($q, $r, 0, 0);
                $hexId = $this->formatHexId($q, $r);

                $hexagons[$hexId] = [
                    'hex_id' => $hexId,
                    'q' => $q,
                    'r' => $r,
                    's' => $s,
                    'ring' => $ring,
                    'center' => $this->hexToLatLng($q, $r, $originLat, $originLng, $hexSizeKm),
                    'vertices' => $this->getHexVertices($q, $r, $originLat, $originLng, $hexSizeKm),
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
