<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create(
            'resident_inventory_assignments',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('resident_id')
                    ->constrained('residents')
                    ->cascadeOnDelete();

                $table->foreignId('resident_stay_id')
                    ->constrained('resident_stays')
                    ->cascadeOnDelete();

                $table->foreignId('inventory_id')
                    ->constrained('inventory')
                    ->restrictOnDelete();

                $table->unsignedInteger('quantity')
                    ->default(1);

                $table->string('condition_at_issue', 50)
                    ->default('good');

                $table->text('issue_notes')->nullable();

                $table->timestamp('assigned_at');

                $table->foreignId('assigned_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->boolean('is_returned')
                    ->default(false);

                $table->unsignedInteger('returned_quantity')
                    ->default(0);

                $table->string('condition_at_return', 50)
                    ->nullable();

                $table->text('return_notes')->nullable();

                $table->timestamp('returned_at')
                    ->nullable();

                $table->foreignId('returned_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->unique(
                    ['resident_stay_id', 'inventory_id'],
                    'stay_inventory_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'resident_inventory_assignments'
        );
    }
};