<?php
// database/migrations/2026_07_08_000001_add_is_active_to_kyc_requirements.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kyc_requirements', function (Blueprint $table) {
            if (!Schema::hasColumn('kyc_requirements', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kyc_requirements', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};