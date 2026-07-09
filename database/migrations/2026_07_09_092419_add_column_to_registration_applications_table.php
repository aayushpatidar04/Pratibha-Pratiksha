<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('registration_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('resident_id')->nullable();
            $table->unsignedBigInteger('allotted_building_id')->nullable();
            $table->unsignedBigInteger('allotted_floor_id')->nullable();
            $table->unsignedBigInteger('allotted_room_id')->nullable();
            $table->unsignedBigInteger('allotted_bed_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registration_applications', function (Blueprint $table) {
            //
        });
    }
};
