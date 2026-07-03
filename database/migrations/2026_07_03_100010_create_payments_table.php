<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('fee_invoices')->cascadeOnDelete();
            $table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_mode', ['cash', 'upi', 'card', 'bank_transfer', 'other']);
            $table->string('transaction_id', 100)->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->string('receipt_number', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
