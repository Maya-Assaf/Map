<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('location_path_points', function (Blueprint $table) {
            $table->id();
            $table->decimal('latitude',10,7);
            $table->decimal('longitude',10,7);
            $table->foreignId('location_path_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_path_points');
    }
};
