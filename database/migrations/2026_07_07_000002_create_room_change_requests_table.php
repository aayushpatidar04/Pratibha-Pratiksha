<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->unsignedBigInteger('current_stay_id')->nullable();
            $table->text('reason')->nullable();

            // What the resident/staff is asking to move to. Building/floor guide the
            // request; room/bed may be chosen upfront or picked at approval time.
            $table->unsignedBigInteger('requested_building_id')->nullable();
            $table->unsignedBigInteger('requested_floor_id')->nullable();
            $table->unsignedBigInteger('requested_room_id')->nullable();
            $table->unsignedBigInteger('requested_bed_id')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_change_requests');
    }
};