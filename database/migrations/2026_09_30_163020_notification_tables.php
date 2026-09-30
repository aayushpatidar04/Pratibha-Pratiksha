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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            
            // Core Content
            $table->string('title')->comment('Notification title');
            $table->text('body')->comment('Notification body/message');
            $table->string('type')->comment('Type of notification (e.g., invoice_payment, admission_created)');
            
            // Payload & Data
            $table->json('payload')->nullable()->comment('Additional data as JSON');
            
            // Action/Reference Link (Polymorphic)
            $table->string('actionable_type')->nullable()->comment('Model class namespace (e.g., App\Models\FeeInvoice)');
            $table->bigInteger('actionable_id')->nullable()->comment('ID of the referenced model');
            
            // Action URL (Optional alternative or additional to polymorphic)
            $table->string('action_url', 500)->nullable()->comment('Direct URL to action');
            $table->string('action_label', 100)->nullable()->comment('Label for the action button');
            
            // Sender Information
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('User who triggered the notification');
            
            // Status
            $table->string('status')->default('sent')->comment('Status: sent, queued, failed, archived');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('type');
            $table->index('status');
            $table->index(['actionable_type', 'actionable_id']);
            $table->index('created_by');
            $table->index('created_at');
        });

        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            // Read Status
            $table->timestamp('read_at')->nullable()->comment('When user read the notification (NULL = unread)');
            
            // User Actions
            $table->timestamp('archived_at')->nullable()->comment('When user archived the notification');
            $table->softDeletes()->comment('Soft delete for user');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('notification_id');
            $table->index('user_id');
            $table->index('read_at');
            
            // Composite indexes for efficient queries
            $table->index(['user_id', 'notification_id']);
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'read_at', 'created_at']);
            
            // Unique constraint to prevent duplicate recipient entries
            $table->unique(['notification_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_recipients');
    }
};
