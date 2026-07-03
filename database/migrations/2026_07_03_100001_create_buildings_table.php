<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->enum('type', ['boys', 'girls', 'mixed']);
            $table->text('address')->nullable();
            $table->integer('total_floors')->default(0);
            $table->integer('total_rooms')->default(0);
            $table->integer('total_capacity')->default(0);
            $table->integer('occupied')->default(0);
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
