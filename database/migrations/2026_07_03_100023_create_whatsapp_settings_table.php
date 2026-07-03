<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('instance_token');
            $table->string('instance_name', 100);
            $table->string('phone_number', 20)->nullable();
            $table->enum('status', ['connected', 'disconnected', 'connecting'])->default('disconnected');
            $table->timestamp('connected_since')->nullable();
            $table->timestamp('last_ping')->nullable();
            $table->integer('messages_sent_today')->default(0);
            $table->integer('failed_count')->default(0);
            $table->string('gateway_url', 500);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};
