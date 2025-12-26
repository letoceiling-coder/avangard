<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Переименовываем message в body (если колонка message существует и body еще нет)
        if (Schema::hasColumn('support_messages', 'message') && !Schema::hasColumn('support_messages', 'body')) {
            DB::statement('ALTER TABLE support_messages CHANGE message body TEXT NOT NULL');
        }
    }

    public function down(): void
    {
        // Переименовываем обратно body в message
        if (Schema::hasColumn('support_messages', 'body') && !Schema::hasColumn('support_messages', 'message')) {
            DB::statement('ALTER TABLE support_messages CHANGE body message TEXT NOT NULL');
        }
    }
};

