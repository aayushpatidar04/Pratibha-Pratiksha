<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notice_updates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('notice_id')
                ->constrained('notices')
                ->cascadeOnDelete();

            $table->string('action');
            // created, updated, published, scheduled,
            // archived, restored

            $table->string('old_status')
                ->nullable();

            $table->string('new_status')
                ->nullable();

            $table->text('remarks')
                ->nullable();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'notice_id',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_updates');
    }
};