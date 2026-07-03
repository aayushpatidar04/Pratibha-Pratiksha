<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->foreignId('floor_id')->constrained('floors')->cascadeOnDelete();
            $table->string('room_number', 20);
            $table->enum('room_type', ['1_seater', '2_seater', '3_seater', '4_seater', '5_seater', 'other']);
            $table->integer('capacity');
            $table->integer('occupied_beds')->default(0);
            $table->decimal('monthly_rent_per_bed', 10, 2)->default(0.00);
            $table->boolean('has_ac')->default(false);
            $table->boolean('has_wifi')->default(false);
            $table->boolean('has_attached_bath')->default(false);
            $table->boolean('has_balcony')->default(false);
            $table->boolean('has_study_table')->default(false);
            $table->enum('status', ['available', 'occupied', 'maintenance', 'partially_occupied'])->default('available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
