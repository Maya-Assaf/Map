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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('old_position');
            $table->string('new_position');
        // الشخص الذي تم تعديل البوزيشن الخاص به
            $table->foreignId('affected_user_id')->constrained('users')->onDelete('cascade');
        // الشخص الذي قام بالتعديل
            $table->foreignId('updated_by_user_id')->constrained('users')->onDelete('cascade');
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
        Schema::dropIfExists('logs');
    }
};
