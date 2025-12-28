<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('village_id')->constrained()->onDelete('cascade');
            $table->string('label')->nullable(); // "Участки 5-10 сот."
            $table->unsignedBigInteger('price')->nullable(); // В копейках
            $table->unsignedBigInteger('unformatted_price')->nullable();
            $table->string('unit')->default('₽');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['village_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_prices');
    }
};

