<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->date('incident_date');
            $table->string('incident_time', 10)->nullable();
            $table->text('description');
            $table->text('witnesses')->nullable();
            $table->enum('warning_level', ['verbal', 'written', 'final', 'suspension', 'expulsion']);
            $table->text('action_taken')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->enum('status', ['open', 'resolved', 'closed'])->default('open');
            $table->boolean('parent_notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_actions');
    }
};
