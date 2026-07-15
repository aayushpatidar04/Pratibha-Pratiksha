<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

        // Add to fee_invoice_items (existing table)
        Schema::table('fee_invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_invoice_items', 'amenity_type')) {
                $table->string('amenity_type')->nullable()->after('item_type'); 
                
            }
            if (!Schema::hasColumn('fee_invoice_items', 'is_late_fee')) {
                $table->boolean('is_late_fee')->default(false)->after('amenity_type');
            }
        });

        // Add to fee_invoices (existing table)
        Schema::table('fee_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_invoices', 'late_fee_amount')) {
                $table->decimal('late_fee_amount', 10, 2)->default(0)->after('paid_amount');
            }
            if (!Schema::hasColumn('fee_invoices', 'late_fee_waived')) {
                $table->boolean('late_fee_waived')->default(false)->after('late_fee_amount');
            }
            if (!Schema::hasColumn('fee_invoices', 'waived_by')) {
                $table->foreignId('waived_by')->nullable()->constrained('users')->after('late_fee_waived');
            }
            if (!Schema::hasColumn('fee_invoices', 'waived_at')) {
                $table->timestamp('waived_at')->nullable()->after('waived_by');
            }
            if (!Schema::hasColumn('fee_invoices', 'waive_reason')) {
                $table->text('waive_reason')->nullable()->after('waived_at');
            }
            if (!Schema::hasColumn('fee_invoices', 'monthly_config_id')) {
                $table->foreignId('monthly_config_id')->nullable()->after('stay_id');
            }
        });

        // Add to payments (existing table)
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });

        // Create payment_proofs table (new)
        if (!Schema::hasTable('payment_proofs')) {
            Schema::create('payment_proofs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
                $table->string('file_path');
                $table->string('file_type');
                $table->string('original_name')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_invoice_items', function (Blueprint $table) {
            $table->dropColumn(['amenity_type', 'is_late_fee']);
        });
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropColumn(['late_fee_amount', 'late_fee_waived', 'waived_by', 'waived_at', 'waive_reason', 'monthly_config_id']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('updated_at');
        });
        Schema::dropIfExists('payment_proofs');
    }
};
