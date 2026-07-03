<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->string('resident_code', 30)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('email', 320)->nullable();
            $table->string('phone', 20);
            $table->string('whatsapp_number', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('blood_group', 10)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->default('India');
            $table->string('pincode', 10)->nullable();
            $table->string('course', 100)->nullable();
            $table->integer('year')->nullable();
            $table->string('batch', 50)->nullable();
            $table->string('roll_number', 50)->nullable();
            $table->string('institute', 200)->nullable();
            $table->string('father_name', 100)->nullable();
            $table->string('father_phone', 20)->nullable();
            $table->string('father_email', 320)->nullable();
            $table->string('mother_name', 100)->nullable();
            $table->string('mother_phone', 20)->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'left', 'upcoming'])->default('upcoming');
            $table->text('photo_url')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('residents');
    }
};
