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
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->foreignId('application_id')
                ->nullable()
                ->after('resident_id')
                ->constrained('registration_applications')
                ->nullOnDelete();

            $table->foreignId('resident_id')
                ->nullable()
                ->change();

            $table->foreignId('stay_id')
                ->nullable()
                ->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('application_id')
                ->nullable()
                ->after('resident_id')
                ->constrained('registration_applications')
                ->nullOnDelete();

            $table->foreignId('resident_id')
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            //
        });
    }
};
