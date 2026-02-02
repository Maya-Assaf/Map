<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('location_spaces', function (Blueprint $table) {
            $table->id();
            $table->decimal('longitude');
            $table->decimal('latitude');
            $table->decimal('radius');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_spaces');
    }
};
