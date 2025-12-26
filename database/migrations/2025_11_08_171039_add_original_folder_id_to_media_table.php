<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->unsignedBigInteger('original_folder_id')->nullable()->after('folder_id');
            $table->timestamp('deleted_at')->nullable()->after('updated_at');
            $table->foreign('original_folder_id')
                ->references('id')
                ->on('folders')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['original_folder_id']);
            $table->dropColumn(['original_folder_id', 'deleted_at']);
        });
    }
};
