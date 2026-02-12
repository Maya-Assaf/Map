<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('location_lines', function (Blueprint $table) {
            $table->id();
            $table->decimal('longitude_a',10,8);
            $table->decimal('latitude_a',10,8);
            $table->decimal('longitude_b',10,8);
            $table->decimal('latitude_b',10,8);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_lines');
    }
};
