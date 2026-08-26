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
        Schema::table('mess_menu', function (Blueprint $table) {
            // $table->dropUnique('mess_menu_unique_building_day_meal');
            // $table->dropColumn('building_id');
            $table->json('items')->change();
            $table->unique(
                ['menu_date', 'meal_type'],
                'mess_menu_date_meal_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mess_menu', function (Blueprint $table) {
            //
        });
    }
};
