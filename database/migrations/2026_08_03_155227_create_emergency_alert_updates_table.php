<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'emergency_alert_updates',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('emergency_alert_id')
                    ->constrained('emergency_alerts')
                    ->cascadeOnDelete();

                $table->string('old_status')
                    ->nullable();

                $table->string('new_status');

                $table->text('remarks')
                    ->nullable();

                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->boolean('updated_by_resident')
                    ->default(false);

                $table->timestamps();

                $table->index([
                    'emergency_alert_id',
                    'created_at',
                ], 'emergency_update_history_index');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'emergency_alert_updates'
        );
    }
};