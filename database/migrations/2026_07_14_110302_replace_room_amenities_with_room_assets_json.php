<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->json('room_assets')
                ->nullable()
                ->after('monthly_rent_per_bed');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn([
                'has_ac',
                'has_wifi',
                'has_attached_bath',
                'has_balcony',
                'has_study_table',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->boolean('has_ac')->default(false);
            $table->boolean('has_wifi')->default(false);
            $table->boolean('has_attached_bath')->default(false);
            $table->boolean('has_balcony')->default(false);
            $table->boolean('has_study_table')->default(false);
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('room_assets');
        });
    }
};