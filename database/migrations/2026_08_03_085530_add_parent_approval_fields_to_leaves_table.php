<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->string('parent_approval_token', 100)
                ->nullable()
                ->unique()
                ->after('parent_approval_status');

            $table->timestamp('parent_approval_sent_at')
                ->nullable()
                ->after('parent_approval_token');

            $table->timestamp('parent_responded_at')
                ->nullable()
                ->after('parent_approval_sent_at');

            $table->text('parent_remarks')
                ->nullable()
                ->after('parent_responded_at');

            $table->text('admin_remarks')
                ->nullable()
                ->after('admin_approval_status');

            $table->timestamp('cancelled_at')
                ->nullable()
                ->after('approved_at');

            $table->foreignId('cancelled_by_resident_id')
                ->nullable()
                ->after('cancelled_at')
                ->constrained('residents')
                ->nullOnDelete();

            $table->index([
                'resident_id',
                'final_status',
                'from_date',
            ], 'leaves_resident_status_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropIndex(
                'leaves_resident_status_date_index'
            );

            $table->dropForeign([
                'cancelled_by_resident_id',
            ]);

            $table->dropUnique([
                'parent_approval_token',
            ]);

            $table->dropColumn([
                'parent_approval_token',
                'parent_approval_sent_at',
                'parent_responded_at',
                'parent_remarks',
                'admin_remarks',
                'cancelled_at',
                'cancelled_by_resident_id',
            ]);
        });
    }
};