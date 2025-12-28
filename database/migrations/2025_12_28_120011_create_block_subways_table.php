<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_subways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained()->onDelete('cascade');
            $table->foreignId('subway_id')->constrained()->onDelete('cascade');
            $table->integer('distance_time')->nullable(); // Время в минутах
            $table->integer('distance_type_id')->nullable(); // 1 = пешком, 2 = транспортом
            $table->string('distance_type')->nullable(); // "пешком", "транспортом"
            $table->integer('priority')->default(500);
            $table->timestamps();
            
            $table->unique(['block_id', 'subway_id']);
            $table->index(['block_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_subways');
    }
};

