<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resident_stays', function (Blueprint $table) {
            $table->string('billing_basis')
                ->default('monthly')
                ->after('bill_type');

            $table->decimal('daily_rate', 10, 2)
                ->nullable()
                ->after('billing_basis');

            $table->foreignId('short_stay_invoice_id')
                ->nullable()
                ->after('daily_rate')
                ->constrained('fee_invoices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('resident_stays', function (Blueprint $table) {
            $table->dropForeign(['short_stay_invoice_id']);

            $table->dropColumn([
                'billing_basis',
                'daily_rate',
                'short_stay_invoice_id',
            ]);
        });
    }
};