<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_requirements', function (Blueprint $table) {
            $table->id();
            // Matches the `documents.document_type` enum — admin picks which of these
            // are mandatory for a resident's KYC to be considered complete.
            $table->enum('document_type', [
                'aadhar_card', 'pan_card', 'photo', 'marksheet',
                'caste_certificate', 'medical_certificate', 'parent_consent', 'other',
            ])->unique();
            $table->string('label', 100);
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_requirements');
    }
};