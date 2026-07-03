<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->enum('vehicle_type', ['two_wheeler', 'four_wheeler', 'bicycle', 'other']);
            $table->string('vehicle_number', 30);
            $table->string('color', 50)->nullable();
            $table->string('model', 100)->nullable();
            $table->text('rc_file_url')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
