<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->enum('category', ['medical', 'fire', 'theft', 'stuck_in_lift', 'need_food', 'disaster', 'domestic_violence', 'threat', 'violence', 'suicidal', 'mental_depression', 'others']);
            $table->text('description')->nullable();
            $table->string('location', 200)->nullable();
            $table->enum('status', ['active', 'resolved', 'escalated'])->default('active');
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_alerts');
    }
};
