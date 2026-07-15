<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monthly_billing_configs', function (Blueprint $table) {
            $table->id();
                $table->year('year');
                $table->tinyInteger('month');
                $table->boolean('rent_enabled')->default(true);
                $table->boolean('mess_enabled')->default(true);
                $table->boolean('cooler_enabled')->default(false);
                $table->json('custom_charges')->nullable(); // [{name, amount}, ...]
                $table->date('generation_date');
                $table->date('due_date');
                $table->decimal('late_fee_amount', 10, 2)->default(0); // Single fixed late fee
                $table->boolean('late_fee_enabled')->default(true);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();

                $table->unique(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_billing_configs');
    }
};
