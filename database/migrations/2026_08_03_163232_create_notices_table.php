<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();

            $table->string('title', 255);

            $table->text('summary')->nullable();

            $table->longText('content');

            $table->string('category')->default('general');
            // general, academic, hostel, mess, maintenance,
            // event, payment, emergency, policy, other

            $table->string('priority')->default('normal');
            // normal, important, urgent

            $table->string('status')->default('draft');
            // draft, scheduled, published, expired, archived

            $table->string('audience_type')->default('all');
            // all, buildings, residents

            $table->boolean('is_pinned')
                ->default(false);

            $table->boolean('requires_acknowledgement')
                ->default(false);

            $table->timestamp('publish_at')
                ->nullable();

            $table->timestamp('expires_at')
                ->nullable();

            $table->timestamp('published_at')
                ->nullable();

            $table->timestamp('archived_at')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'status',
                'publish_at',
                'expires_at',
            ]);

            $table->index([
                'priority',
                'is_pinned',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};