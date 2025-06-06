<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            // $table->string('position')->nullable();
            // $table->string('department')->nullable();
            // $table->string('layer')->nullable();
            // $table->enum('position', ['Head', 'CoHead', 'Senior leader', 'Junior leader', 'Volunteer']);
            $table->string('position',100);
            // $table->enum('department', ['IT&AI', 'Research', 'Design', 'Admin', 'Education', 'Media', 'Fundrising']);
            $table->string('department', 100);
            $table->string('layer' , 100)->default('other');
            // $table->enum('layer', ['public health', 'resources management', 'economic factor', 'urban planning', 'ecological factor', 'social factor', 'building code', 'Culture and heritage', 'technology and infrastructure', 'data collection and analysis']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
