<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('location_polygon_points', function (Blueprint $table) {
            $table->id();
            $table->decimal('latitude');
            $table->decimal('longitude');
            $table->foreignId('location_polygon_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_polygon_points');
    }
};
