<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->unsignedBigInteger('room_id')->nullable();
            $table->enum('category', ['electrical', 'plumbing', 'furniture', 'wifi', 'cleaning', 'security', 'food', 'other']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('title', 200);
            $table->text('description');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'escalated', 'rejected'])->default('open');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->integer('rating')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
