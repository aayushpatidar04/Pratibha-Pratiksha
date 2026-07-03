<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->string('item_name', 100);
            $table->enum('category', ['room', 'student', 'common']);
            $table->integer('total_quantity')->default(0);
            $table->integer('in_use')->default(0);
            $table->integer('available')->default(0);
            $table->integer('damaged')->default(0);
            $table->string('unit', 20)->default('pieces');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
