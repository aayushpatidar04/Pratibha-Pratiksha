<?php
// database/migrations/2026_07_06_000001_create_registration_applications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registration_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_no')->unique();
            $table->string('status')->default('pending'); // pending, paid, approved, rejected

            // Personal Details
            $table->string('student_name');
            $table->string('father_name');
            $table->string('mother_name');
            $table->date('dob');
            $table->integer('age');
            $table->string('blood_group')->nullable();
            $table->string('student_photo')->nullable();

            // Contact
            $table->string('student_mobile');
            $table->string('father_mobile')->nullable();
            $table->string('mother_mobile')->nullable();
            $table->string('email')->nullable();

            // Address
            $table->text('permanent_address');
            $table->text('current_address')->nullable();

            // Education
            $table->string('institution_name');
            $table->text('institution_address')->nullable();
            $table->string('course_name');
            $table->string('course_duration');

            // Hostel Details
            $table->string('room_type'); // 2-seater, 3-seater, 4-seater
            $table->date('stay_duration_from')->nullable();
            $table->date('stay_duration_to')->nullable();
            $table->boolean('has_driving_license')->default(false);

            // Vehicle
            $table->string('vehicle_type')->nullable(); // two_wheeler, four_wheeler
            $table->string('vehicle_number')->nullable();

            // Health
            $table->text('disease_history')->nullable();
            $table->text('allergy_details')->nullable();

            // Achievements
            $table->text('special_achievements')->nullable();

            // Guardians (Local)
            $table->string('guardian1_name')->nullable();
            $table->string('guardian1_mobile')->nullable();
            $table->string('guardian1_occupation')->nullable();
            $table->text('guardian1_address')->nullable();

            $table->string('guardian2_name')->nullable();
            $table->string('guardian2_mobile')->nullable();
            $table->string('guardian2_occupation')->nullable();
            $table->text('guardian2_address')->nullable();

            // Family Photos
            $table->string('father_photo')->nullable();
            $table->string('mother_photo')->nullable();
            $table->string('family_photo1')->nullable();
            $table->string('family_photo2')->nullable();
            $table->string('guardian_photo')->nullable();

            // Payment
            $table->string('payment_method')->nullable(); // razorpay, cash
            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();
            $table->decimal('registration_fee', 10, 2)->default(300.00);
            $table->timestamp('paid_at')->nullable();

            // Admin
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('admin_remarks')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_applications');
    }
};