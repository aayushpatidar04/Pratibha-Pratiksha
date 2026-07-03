<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->enum('leave_type', ['home_leave', 'medical_leave', 'emergency_leave', 'day_out', 'night_pass']);
            $table->date('from_date');
            $table->date('to_date');
            $table->text('reason');
            $table->string('destination', 200)->nullable();
            $table->enum('parent_approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('admin_approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('final_status', ['pending', 'parent_approval_pending', 'approved', 'rejected', 'cancelled', 'expired'])->default('pending');
            $table->string('gate_pass_code', 50)->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
