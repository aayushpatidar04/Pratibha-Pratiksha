<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gate_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->enum('pass_type', ['day_out', 'night_pass', 'visitor_pass', 'late_entry']);
            $table->timestamp('from_time');
            $table->timestamp('to_time');
            $table->text('purpose')->nullable();
            $table->string('visitor_name', 100)->nullable();
            $table->string('visitor_phone', 20)->nullable();
            $table->string('visitor_id_proof', 100)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'expired', 'used'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gate_passes');
    }
};
