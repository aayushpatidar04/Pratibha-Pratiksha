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
            $table->unique(['menu_date', 'meal_type'], 'mess_menu_unique_day_meal');
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
