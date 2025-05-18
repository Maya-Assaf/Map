<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationReferencesTable extends Migration
{
    public function up()
    {
        Schema::create('location_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->string('pdf_path');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('location_references');
    }
}
