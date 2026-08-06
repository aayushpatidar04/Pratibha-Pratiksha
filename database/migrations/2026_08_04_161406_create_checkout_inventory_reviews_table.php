<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'checkout_inventory_reviews',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('checkout_request_id')
                    ->constrained('checkout_requests')
                    ->cascadeOnDelete();

                $table->foreignId(
                    'resident_inventory_assignment_id'
                )
                    ->constrained(
                        'resident_inventory_assignments'
                    )
                    ->cascadeOnDelete()->name('fk_checkout_reviews_resident_assignment');

                $table->foreignId('inventory_id')
                    ->constrained('inventory')
                    ->restrictOnDelete();

                $table->unsignedInteger(
                    'assigned_quantity'
                );

                $table->unsignedInteger(
                    'returned_good_quantity'
                )->default(0);

                $table->unsignedInteger(
                    'returned_damaged_quantity'
                )->default(0);

                $table->unsignedInteger(
                    'missing_quantity'
                )->default(0);

                $table->string('condition_at_review')
                    ->nullable();

                $table->text('review_notes')
                    ->nullable();

                $table->decimal(
                    'damage_charge',
                    12,
                    2
                )->default(0);

                $table->foreignId('reviewed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->dateTime('reviewed_at')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'checkout_request_id',
                        'resident_inventory_assignment_id',
                    ],
                    'checkout_request_inventory_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'checkout_inventory_reviews'
        );
    }
};