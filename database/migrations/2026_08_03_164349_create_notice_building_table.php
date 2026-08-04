<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notice_building', function (Blueprint $table) {
            $table->id();

            $table->foreignId('notice_id')
                ->constrained('notices')
                ->cascadeOnDelete();

            $table->foreignId('building_id')
                ->constrained('buildings')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique([
                'notice_id',
                'building_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_building');
    }
};