<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->enum('recipient_type', ['resident', 'parent', 'all_residents', 'all_parents', 'staff']);
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('recipient_phone', 20);
            $table->enum('message_type', ['text', 'image', 'document', 'template'])->default('text');
            $table->string('template_name', 100)->nullable();
            $table->text('content');
            $table->text('media_url')->nullable();
            $table->enum('status', ['sent', 'delivered', 'read', 'failed', 'scheduled'])->default('sent');
            $table->string('wa_message_id', 100)->nullable();
            $table->text('failed_reason')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
