<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('room_change_requests', function (Blueprint $table) {
            $table->date('effective_from')
                ->nullable()
                ->after('requested_bed_id');

            $table->string('new_billing_basis')
                ->nullable()
                ->after('effective_from');

            $table->decimal('new_rent_amount', 10, 2)
                ->nullable()
                ->after('new_billing_basis');

            $table->decimal('new_daily_rate', 10, 2)
                ->nullable()
                ->after('new_rent_amount');

            $table->date('new_expected_check_out_date')
                ->nullable()
                ->after('new_daily_rate');

            $table->foreignId('new_stay_id')
                ->nullable()
                ->after('current_stay_id')
                ->constrained('resident_stays')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('room_change_requests', function (Blueprint $table) {
            $table->dropForeign(['new_stay_id']);

            $table->dropColumn([
                'effective_from',
                'new_billing_basis',
                'new_rent_amount',
                'new_daily_rate',
                'new_expected_check_out_date',
                'new_stay_id',
            ]);
        });
    }
};