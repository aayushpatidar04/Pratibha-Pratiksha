<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_stays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->foreignId('building_id')->constrained('buildings')->cascadeOnDelete();
            $table->foreignId('floor_id')->constrained('floors')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('bed_id')->constrained('beds')->cascadeOnDelete();
            $table->date('check_in_date');
            $table->date('expected_check_out_date')->nullable();
            $table->date('actual_check_out_date')->nullable();
            $table->decimal('rent_amount', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->default(0.00);
            $table->enum('bill_type', ['monthly', 'session'])->default('monthly');
            $table->enum('status', ['active', 'ended', 'upcoming'])->default('upcoming');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_stays');
    }
};
