<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('residents')->cascadeOnDelete();
            $table->foreignId('stay_id')->constrained('resident_stays')->cascadeOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->enum('fee_type', ['hostel_fee', 'mess_fee', 'security_deposit', 'other']);
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->enum('status', ['pending', 'paid', 'partial', 'overdue', 'waived'])->default('pending');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_invoices');
    }
};
