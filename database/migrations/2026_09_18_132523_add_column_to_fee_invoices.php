<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->string('donator_name')->nullable()->after('description');
            $table->string('donator_address')->nullable()->after('donator_name');
            $table->string('donator_phone')->nullable()->after('donator_address');
        });
    }

    public function down(): void
    {
        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropColumn(['donator_name', 'donator_address', 'donator_phone']);
        });
    }
};
