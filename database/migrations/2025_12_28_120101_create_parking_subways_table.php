<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parking_subways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_id')->constrained()->onDelete('cascade');
            $table->foreignId('subway_id')->constrained()->onDelete('cascade');
            $table->integer('distance_time')->nullable();
            $table->integer('distance_type_id')->nullable();
            $table->integer('priority')->default(500);
            $table->timestamps();
            
            $table->unique(['parking_id', 'subway_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parking_subways');
    }
};

