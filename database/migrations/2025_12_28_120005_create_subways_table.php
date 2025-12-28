<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subway_line_id')->constrained()->onDelete('cascade');
            $table->foreignId('city_id')->constrained()->onDelete('cascade');
            $table->string('guid')->unique();
            $table->string('name');
            $table->unsignedBigInteger('crm_id')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('priority')->default(500);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['subway_line_id', 'city_id']);
            $table->index(['latitude', 'longitude']);
            $table->index(['guid', 'city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subways');
    }
};

