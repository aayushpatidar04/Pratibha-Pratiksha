<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table(
            'resident_inventory_assignments',
            function (Blueprint $table) {
                $table->unsignedInteger('returned_good_quantity')
                    ->default(0)
                    ->after('returned_quantity');

                $table->unsignedInteger('returned_damaged_quantity')
                    ->default(0)
                    ->after('returned_good_quantity');

                $table->unsignedInteger('missing_quantity')
                    ->default(0)
                    ->after('returned_damaged_quantity');

                $table->string('return_review_status')
                    ->default('pending')
                    ->after('missing_quantity');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'resident_inventory_assignments',
            function (Blueprint $table) {
                $table->dropColumn([
                    'returned_good_quantity',
                    'returned_damaged_quantity',
                    'missing_quantity',
                    'return_review_status',
                ]);
            }
        );
    }
};