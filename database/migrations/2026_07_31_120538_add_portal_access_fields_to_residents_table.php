<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            if (!Schema::hasColumn('residents', 'portal_enabled')) {
                $table->boolean('portal_enabled')
                    ->default(false)
                    ->after('status');
            }

            if (!Schema::hasColumn('residents', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }

            if (!Schema::hasColumn('residents', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable();
            }

            if (!Schema::hasColumn('residents', 'must_change_password')) {
                $table->boolean('must_change_password')
                    ->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn([
                'portal_enabled',
                'last_login_at',
                'password_changed_at',
                'must_change_password',
            ]);
        });
    }
};