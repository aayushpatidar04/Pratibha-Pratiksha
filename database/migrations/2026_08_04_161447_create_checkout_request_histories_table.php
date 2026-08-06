<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'checkout_request_histories',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('checkout_request_id')
                    ->constrained('checkout_requests')
                    ->cascadeOnDelete();

                $table->string('action');

                $table->string('from_status')
                    ->nullable();

                $table->string('to_status')
                    ->nullable();

                /*
                 * actor_type:
                 * resident, user or system
                 */
                $table->string('actor_type')
                    ->nullable();

                $table->unsignedBigInteger('actor_id')
                    ->nullable();

                $table->text('notes')
                    ->nullable();

                $table->json('metadata')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'checkout_request_id',
                    'created_at',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'checkout_request_histories'
        );
    }
};