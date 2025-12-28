<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subway_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('color', 7)->nullable(); // Hex цвет (#2489c2)
            $table->integer('line_number')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['city_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subway_lines');
    }
};

