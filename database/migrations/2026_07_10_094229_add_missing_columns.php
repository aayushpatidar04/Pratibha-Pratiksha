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
    }
};
