<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained()->onDelete('cascade');
            $table->integer('room_type_id')->nullable(); // Код типа (60 = свободная планировка)
            $table->string('room_type_name')->nullable(); // "Студия", "1-к", "2-к"
            $table->unsignedBigInteger('price')->nullable(); // В копейках
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['block_id', 'room_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_prices');
    }
};

