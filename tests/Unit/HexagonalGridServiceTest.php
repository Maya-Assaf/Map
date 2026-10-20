<?php

namespace Tests\Unit;

use App\Services\HexagonalGridService;
use PHPUnit\Framework\TestCase;

class HexagonalGridServiceTest extends TestCase
{
    private HexagonalGridService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HexagonalGridService();
    }

    public function test_origin_point_maps_to_center_hexagon(): void
    {
        $originLat = 33.5138;
        $originLng = 36.2765;

        $hex = $this->service->latLngToHex($originLat, $originLng, $originLat, $originLng, 1.0);

        $this->assertSame('hex_q0_r0', $hex['hex_id']);
        $this->assertSame(0, $hex['q']);
        $this->assertSame(0, $hex['r']);
        $this->assertSame(0, $hex['s']);
        $this->assertSame(0, $hex['ring']);
        $this->assertEqualsWithDelta($originLat, $hex['center']['latitude'], 0.0001);
        $this->assertEqualsWithDelta($originLng, $hex['center']['longitude'], 0.0001);
    }

    public function test_generate_k_ring_grid_returns_correct_hexagon_counts_and_vertices(): void
    {
        $originLat = 33.5138;
        $originLng = 36.2765;

        $grid0 = $this->service->generateKRingGrid($originLat, $originLng, 1.0, 0);
        $this->assertCount(1, $grid0);

        $grid1 = $this->service->generateKRingGrid($originLat, $originLng, 1.0, 1);
        $this->assertCount(7, $grid1);

        $grid2 = $this->service->generateKRingGrid($originLat, $originLng, 1.0, 2);
        $this->assertCount(19, $grid2);

        foreach ($grid1 as $hex) {
            $this->assertSame(0, $hex['q'] + $hex['r'] + $hex['s']);
            $this->assertCount(6, $hex['vertices']);
            $this->assertLessThanOrEqual(1, $hex['ring']);
        }
    }

    public function test_points_in_neighbor_hexagons_map_to_expected_rings(): void
    {
        $originLat = 33.5138;
        $originLng = 36.2765;
        $hexSizeKm = 1.0;

        // Center of neighbor hexagon (q=1, r=0)
        $neighborCenter = $this->service->hexToLatLng(1, 0, $originLat, $originLng, $hexSizeKm);
        $mappedHex = $this->service->latLngToHex(
            $neighborCenter['latitude'],
            $neighborCenter['longitude'],
            $originLat,
            $originLng,
            $hexSizeKm
        );

        $this->assertSame(1, $mappedHex['q']);
        $this->assertSame(0, $mappedHex['r']);
        $this->assertSame(1, $mappedHex['ring']);
    }
}
