<?php
// database/migrations/2026_07_09_000005_create_resident_amenity_overrides_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resident_amenity_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->boolean('rent_enabled')->default(true);
            $table->boolean('mess_enabled')->default(true);
            $table->boolean('cooler_enabled')->default(false);
            $table->decimal('custom_rent', 10, 2)->nullable();      // override default rent
            $table->decimal('custom_mess', 10, 2)->nullable();      // override default mess
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique('resident_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_amenity_overrides');
    }
};