<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resident_stays', function (Blueprint $table) {
            $table->boolean('check_in_status')
                ->default(false)
                ->after('check_in_date');

            $table->timestamp('checked_in_at')
                ->nullable()
                ->after('check_in_status');

            $table->foreignId('checked_in_by')
                ->nullable()
                ->after('checked_in_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('resident_stays', function (Blueprint $table) {
            $table->dropForeign(['checked_in_by']);

            $table->dropColumn([
                'check_in_status',
                'checked_in_at',
                'checked_in_by',
            ]);
        });
    }
};