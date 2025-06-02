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
        Schema::create('pre_registered_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->enum('department', [
                'ADMIN DEPARTMENT',
                'DESIGN DEPARTMENT',
                'EDUCATION DEPARTMENT',
                'FUNDRAISING & NETWORKING DEPARTMENT',
                'FUNDRAISING DEPARTMENT',
                'IT & AI DEPARTMENT',
                'MEDIA DEPARTMENT',
                'RESEARCH DEPARTMENT',
            ]);

            $table->enum('position', [
                    'Co-Head',
                    'Co-Head, Senior Leader',
                    'Corrdinator',
                    'Corrdinator, Junior Leader',
                    'Head',
                    'Junior',
                    'Junior Leader',
                    'Junior Leader, Corrdinator',
                    'Senior Leader',
                    'Senior Leader, Co-Head',
                    'null'
                    
                ])-> nullable();

            $table->enum('layer', [
                    'Culture and heritage',
                    'Other',
                    'building code',
                    'data collection and analysis',
                    'ecological factor',
                    'economic factor',
                    'public health',
                    'resources management',
                    'social factor',
                    'technology and infrastructure',
                    'urban planning',
                ])->default('Other');

            $table->enum('status', ['Active', 'Pause', 'Stop']);
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
        Schema::dropIfExists('pre_registered_users');
    }
};
