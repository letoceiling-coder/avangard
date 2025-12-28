<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builders', function (Blueprint $table) {
            $table->id();
            $table->string('guid')->unique();
            $table->string('name');
            $table->unsignedBigInteger('crm_id')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_exclusive')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['guid', 'is_active']);
            $table->index('is_exclusive');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builders');
    }
};

