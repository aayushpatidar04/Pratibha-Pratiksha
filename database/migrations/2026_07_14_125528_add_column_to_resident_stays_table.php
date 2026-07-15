<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('resident_stays', function (Blueprint $table) {
            $table->foreignId('deposit_transferred_from_stay_id')
                ->nullable()
                ->after('deposit_amount')
                ->constrained('resident_stays')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resident_stays', function (Blueprint $table) {
            //
        });
    }
};
