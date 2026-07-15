<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resident_stays', function (Blueprint $table) {
            $table->string('checkout_status')
                ->default('not_requested')
                ->after('actual_check_out_date');

            $table->text('checkout_notes')
                ->nullable()
                ->after('checkout_status');

            $table->foreignId('checkout_reviewed_by')
                ->nullable()
                ->after('checkout_notes')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('checkout_reviewed_at')
                ->nullable()
                ->after('checkout_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('resident_stays', function (Blueprint $table) {
            $table->dropForeign(['checkout_reviewed_by']);

            $table->dropColumn([
                'checkout_status',
                'checkout_notes',
                'checkout_reviewed_by',
                'checkout_reviewed_at',
            ]);
        });
    }
};