<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mess_menu', function (Blueprint $table) {

            // Remove old unique index
            $table->dropUnique('mess_menu_unique_day_meal');

            // Create new one
            $table->unique(
                ['building_id', 'menu_date', 'meal_type'],
                'mess_menu_unique_building_day_meal'
            );
        });
    }

    public function down(): void
    {
        Schema::table('mess_menu', function (Blueprint $table) {

            $table->dropUnique(
                'mess_menu_unique_building_day_meal'
            );

            $table->unique(
                ['menu_date', 'meal_type'],
                'mess_menu_unique_day_meal'
            );
        });
    }
};