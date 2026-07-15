<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('monthly_billing_configs', function (Blueprint $table) {
            $table->decimal('default_mess_amount', 10, 2)
                ->default(0)
                ->after('mess_enabled');

            $table->decimal('default_cooler_amount', 10, 2)
                ->default(0)
                ->after('cooler_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_billing_configs', function (Blueprint $table) {
            $table->dropColumn([
                'default_mess_amount',
                'default_cooler_amount',
            ]);
        });
    }
};