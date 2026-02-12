<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('location_spaces', function (Blueprint $table) {
            $table->id();
            $table->decimal('longitude',10 ,7);
            $table->decimal('latitude',10,7);
            $table->decimal('radius', 8, 2); // مثلاً 1500.50 متر
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_spaces');
    }
};
