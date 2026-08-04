<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notice_reads', function (Blueprint $table) {
            $table->id();

            $table->foreignId('notice_id')
                ->constrained('notices')
                ->cascadeOnDelete();

            $table->foreignId('resident_id')
                ->constrained('residents')
                ->cascadeOnDelete();

            $table->timestamp('first_read_at')
                ->nullable();

            $table->timestamp('last_read_at')
                ->nullable();

            $table->unsignedInteger('read_count')
                ->default(0);

            $table->timestamp('acknowledged_at')
                ->nullable();

            $table->string('acknowledgement_ip', 45)
                ->nullable();

            $table->timestamps();

            $table->unique([
                'notice_id',
                'resident_id',
            ]);

            $table->index([
                'resident_id',
                'acknowledged_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_reads');
    }
};