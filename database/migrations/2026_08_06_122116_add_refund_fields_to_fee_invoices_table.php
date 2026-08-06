<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->string('refund_status')
                ->default('not_refunded')
                ->after('late_fee_frozen_at');

            $table->decimal('refund_amount', 12, 2)
                ->default(0)
                ->after('refund_status');

            $table->dateTime('refunded_at')
                ->nullable()
                ->after('refund_amount');

            $table->string('refund_transaction_id')
                ->nullable()
                ->after('refunded_at');

            $table->text('refund_notes')
                ->nullable()
                ->after('refund_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'refund_status',
                'refund_amount',
                'refunded_at',
                'refund_transaction_id',
                'refund_notes',
            ]);
        });
    }
};