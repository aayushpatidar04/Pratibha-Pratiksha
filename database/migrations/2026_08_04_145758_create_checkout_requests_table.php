<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resident_id')
                ->constrained('residents')
                ->cascadeOnDelete();

            $table->foreignId('resident_stay_id')
                ->constrained('resident_stays')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Request origin
            |--------------------------------------------------------------------------
            |
            | Resident may create the request through the resident portal.
            | Admin/HR may also create it on behalf of the resident.
            |
            */

            $table->string('requested_by_type')
                ->default('resident');

            $table->unsignedBigInteger('requested_by_id')
                ->nullable();

            $table->date('requested_checkout_date');

            $table->dateTime('requested_at');

            /*
            |--------------------------------------------------------------------------
            | Notice-period calculation
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('required_notice_days')
                ->default(30);

            $table->unsignedInteger('actual_notice_days')
                ->default(0);

            $table->boolean('is_short_notice')
                ->default(false);

            $table->boolean('short_notice_warning_accepted')
                ->default(false);

            $table->dateTime('warning_accepted_at')
                ->nullable();

            $table->decimal('short_notice_charge', 12, 2)
                ->default(0);

            $table->text('short_notice_charge_notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Resident/request details
            |--------------------------------------------------------------------------
            */

            $table->text('reason');

            $table->text('resident_notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Main workflow status
            |--------------------------------------------------------------------------
            */

            $table->string('status')
                ->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Preliminary admin review
            |--------------------------------------------------------------------------
            */

            $table->string('admin_review_status')
                ->default('pending');

            $table->foreignId('admin_reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('admin_reviewed_at')
                ->nullable();

            $table->text('admin_review_notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Warden assignment and inspection
            |--------------------------------------------------------------------------
            */

            $table->foreignId('assigned_warden_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('warden_assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('warden_assigned_at')
                ->nullable();

            $table->string('warden_review_status')
                ->default('not_assigned');

            $table->dateTime('warden_reviewed_at')
                ->nullable();

            $table->text('warden_review_notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Final admin decision
            |--------------------------------------------------------------------------
            */

            $table->foreignId('final_approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('final_approved_at')
                ->nullable();

            $table->text('final_approval_notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Financial clearance
            |--------------------------------------------------------------------------
            */

            $table->string('dues_clearance_status')
                ->default('pending');

            $table->decimal(
                'outstanding_amount_at_request',
                12,
                2
            )->default(0);

            $table->decimal('short_notice_charge_final', 12, 2)
                ->default(0);

            $table->decimal('asset_damage_charge', 12, 2)
                ->default(0);

            $table->decimal('other_checkout_charge', 12, 2)
                ->default(0);

            $table->text('charge_notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Exit QR/token
            |--------------------------------------------------------------------------
            */

            $table->string('exit_token', 100)
                ->nullable()
                ->unique();

            $table->dateTime('exit_token_generated_at')
                ->nullable();

            $table->dateTime('exit_token_expires_at')
                ->nullable();

            $table->foreignId('exit_token_generated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Guard verification
            |--------------------------------------------------------------------------
            */

            $table->foreignId('gate_verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('gate_verified_at')
                ->nullable();

            $table->text('gate_verification_notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Final completion
            |--------------------------------------------------------------------------
            */

            $table->dateTime('actual_checkout_at')
                ->nullable();

            $table->foreignId('completed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('completion_notes')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Cancellation
            |--------------------------------------------------------------------------
            */

            $table->string('cancelled_by_type')
                ->nullable();

            $table->unsignedBigInteger('cancelled_by_id')
                ->nullable();

            $table->dateTime('cancelled_at')
                ->nullable();

            $table->text('cancellation_reason')
                ->nullable();

            $table->timestamps();

            $table->index([
                'resident_id',
                'status',
            ]);

            $table->index([
                'resident_stay_id',
                'status',
            ]);

            $table->index([
                'assigned_warden_id',
                'warden_review_status',
            ]);

            $table->index([
                'requested_checkout_date',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_requests');
    }
};