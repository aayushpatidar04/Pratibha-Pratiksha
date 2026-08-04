<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'room_change_requests',
            function (Blueprint $table): void {
                $table->foreignId(
                    'requested_by_resident_id'
                )
                    ->nullable()
                    ->after('requested_by')
                    ->constrained('residents')
                    ->nullOnDelete();

                $table->timestamp('cancelled_at')
                    ->nullable()
                    ->after('reviewed_at');

                $table->string('request_source', 30)
                    ->default('admin')
                    ->after('status');

                $table->index([
                    'resident_id',
                    'status',
                    'created_at',
                ], 'room_change_resident_status_index');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'room_change_requests',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'room_change_resident_status_index'
                );

                $table->dropForeign([
                    'requested_by_resident_id',
                ]);

                $table->dropColumn([
                    'requested_by_resident_id',
                    'cancelled_at',
                    'request_source',
                ]);
            }
        );
    }
};