<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Удаляет все таблицы, связанные с поддержкой
     */
    public function up(): void
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();

        // Шаг 1: Удаляем foreign keys через прямой SQL (безопаснее)
        // Получаем все foreign keys для таблиц поддержки
        $foreignKeys = DB::select("
            SELECT 
                CONSTRAINT_NAME,
                TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME IN ('support_tickets', 'support_ticket_messages', 'ticket_chats', 'message_sync_logs')
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database]);

        // Удаляем каждый foreign key
        foreach ($foreignKeys as $fk) {
            try {
                DB::statement("ALTER TABLE `{$fk->TABLE_NAME}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            } catch (\Exception $e) {
                // Игнорируем ошибку, если foreign key уже удален
            }
        }

        // Шаг 2: Удаляем таблицы в правильном порядке (сначала зависимые)
        if (Schema::hasTable('message_sync_logs')) {
            Schema::dropIfExists('message_sync_logs');
        }

        if (Schema::hasTable('support_ticket_messages')) {
            Schema::dropIfExists('support_ticket_messages');
        }

        // Шаг 3: Удаляем таблицу support_tickets
        if (Schema::hasTable('support_tickets')) {
            Schema::dropIfExists('support_tickets');
        }

        // Шаг 4: Удаляем ticket_chats (после удаления support_tickets)
        if (Schema::hasTable('ticket_chats')) {
            Schema::dropIfExists('ticket_chats');
        }
    }

    /**
     * Reverse the migrations.
     * ВНИМАНИЕ: Эта миграция не восстанавливает таблицы поддержки
     * Если нужен откат, используйте git для восстановления миграций
     */
    public function down(): void
    {
        // Не восстанавливаем таблицы, так как функциональность поддержки удалена
        // Для восстановления используйте git checkout соответствующих миграций
    }
};
