<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('location_points', function (Blueprint $table) {
            $table->index(['latitude', 'longitude'], 'idx_loc_points_lat_lng');
        });

        Schema::table('location_spaces', function (Blueprint $table) {
            $table->index(['latitude', 'longitude'], 'idx_loc_spaces_lat_lng');
        });

        Schema::table('location_polygon_points', function (Blueprint $table) {
            $table->index(['latitude', 'longitude', 'location_polygon_id'], 'idx_loc_poly_pts_lat_lng_poly');
            $table->index('location_polygon_id', 'idx_loc_poly_pts_poly_id');
        });

        Schema::table('location_path_points', function (Blueprint $table) {
            $table->index(['latitude', 'longitude', 'location_path_id'], 'idx_loc_path_pts_lat_lng_path');
            $table->index('location_path_id', 'idx_loc_path_pts_path_id');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->index(['category_id', 'locatable_type', 'locatable_id'], 'idx_locations_cat_morph');
            $table->index(['sub_aspect_id', 'locatable_type', 'locatable_id'], 'idx_locations_sub_morph');
            $table->index(['aspect_id', 'locatable_type', 'locatable_id'], 'idx_locations_asp_morph');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::select('ANALYZE TABLE location_points, location_spaces, location_polygon_points, location_path_points, locations');
        } elseif ($driver === 'sqlite') {
            DB::statement('ANALYZE');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('idx_locations_asp_morph');
            $table->dropIndex('idx_locations_sub_morph');
            $table->dropIndex('idx_locations_cat_morph');
        });

        Schema::table('location_path_points', function (Blueprint $table) {
            $table->dropIndex('idx_loc_path_pts_path_id');
            $table->dropIndex('idx_loc_path_pts_lat_lng_path');
        });

        Schema::table('location_polygon_points', function (Blueprint $table) {
            $table->dropIndex('idx_loc_poly_pts_poly_id');
            $table->dropIndex('idx_loc_poly_pts_lat_lng_poly');
        });

        Schema::table('location_spaces', function (Blueprint $table) {
            $table->dropIndex('idx_loc_spaces_lat_lng');
        });

        Schema::table('location_points', function (Blueprint $table) {
            $table->dropIndex('idx_loc_points_lat_lng');
        });
    }
};
