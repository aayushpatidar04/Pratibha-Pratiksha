<?php
// database/migrations/2026_07_15_000001_add_late_fee_per_day_to_billing_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_billing_configs', function (Blueprint $table) {
            $table->decimal('late_fee_per_day', 10, 2)->nullable()->after('late_fee_amount');
        });

        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->decimal('late_fee_per_day', 10, 2)->nullable()->after('late_fee_amount');
            $table->date('late_fee_frozen_at')->nullable()->after('late_fee_waived');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_billing_configs', function (Blueprint $table) {
            $table->dropColumn('late_fee_per_day');
        });

        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropColumn(['late_fee_per_day', 'late_fee_frozen_at']);
        });
    }
};