<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emergency_alerts', function (Blueprint $table) {
            $table->foreignId('building_id')
                ->nullable()
                ->after('resident_id')
                ->constrained('buildings')
                ->nullOnDelete();

            $table->foreignId('room_id')
                ->nullable()
                ->after('building_id')
                ->constrained('rooms')
                ->nullOnDelete();

            $table->foreignId('assigned_to')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('acknowledged_by')
                ->nullable()
                ->after('assigned_to')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('acknowledged_at')
                ->nullable()
                ->after('acknowledged_by');

            $table->text('escalation_notes')
                ->nullable()
                ->after('acknowledged_at');

            $table->text('resolution_notes')
                ->nullable()
                ->after('escalation_notes');

            $table->timestamp('updated_at')
                ->nullable()
                ->after('created_at');

            $table->index([
                'resident_id',
                'status',
                'created_at',
            ], 'emergency_resident_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('emergency_alerts', function (Blueprint $table) {
            $table->dropIndex(
                'emergency_resident_status_index'
            );

            $table->dropForeign(['building_id']);
            $table->dropForeign(['room_id']);
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['acknowledged_by']);

            $table->dropColumn([
                'building_id',
                'room_id',
                'assigned_to',
                'acknowledged_by',
                'acknowledged_at',
                'escalation_notes',
                'resolution_notes',
                'updated_at',
            ]);
        });
    }
};