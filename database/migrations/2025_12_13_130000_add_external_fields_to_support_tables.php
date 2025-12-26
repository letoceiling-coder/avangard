<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Добавляем external_id в support_tickets (если еще не существует)
        if (!Schema::hasColumn('support_tickets', 'external_id')) {
            Schema::table('support_tickets', function (Blueprint $table) {
                $table->uuid('external_id')->nullable()->after('id');
            });
        }
        
        // Добавляем индекс для external_id (если еще не существует)
        $indexes = DB::select("SHOW INDEXES FROM support_tickets WHERE Key_name = 'support_tickets_external_id_index'");
        if (empty($indexes)) {
            try {
                Schema::table('support_tickets', function (Blueprint $table) {
                    $table->index('external_id');
                });
            } catch (\Exception $e) {
                // Индекс уже существует или другая ошибка - игнорируем
            }
        }

        // Переименовываем theme в subject (MySQL требует через DB::statement)
        if (Schema::hasColumn('support_tickets', 'theme')) {
            DB::statement('ALTER TABLE support_tickets CHANGE theme subject VARCHAR(255) NOT NULL');
        }

        // Добавляем external_message_id в support_messages (если еще не существует)
        if (!Schema::hasColumn('support_messages', 'external_message_id')) {
            Schema::table('support_messages', function (Blueprint $table) {
                $table->string('external_message_id')->nullable()->after('id');
            });
        }
        
        // Добавляем индекс для external_message_id (если еще не существует)
        $indexes = DB::select("SHOW INDEXES FROM support_messages WHERE Key_name = 'support_messages_external_message_id_index'");
        if (empty($indexes)) {
            try {
                Schema::table('support_messages', function (Blueprint $table) {
                    $table->index('external_message_id');
                });
            } catch (\Exception $e) {
                // Индекс уже существует или другая ошибка - игнорируем
            }
        }

        // Переименовываем message в body (если колонка message существует)
        if (Schema::hasColumn('support_messages', 'message') && !Schema::hasColumn('support_messages', 'body')) {
            DB::statement('ALTER TABLE support_messages CHANGE message body TEXT NOT NULL');
        }

        // Изменяем enum sender: local|crm → tma|crm
        // Проверяем текущий тип колонки, чтобы не выполнять лишние операции
        $columnInfo = DB::select("SHOW COLUMNS FROM support_messages WHERE Field = 'sender'");
        if (!empty($columnInfo)) {
            $columnType = $columnInfo[0]->Type;
            
            // Если ENUM еще содержит 'local', выполняем миграцию
            if (str_contains($columnType, 'local')) {
                // Сначала расширяем ENUM, чтобы включить 'tma' (если его еще нет)
                // Это безопасно, так как не удаляет существующие значения
                if (!str_contains($columnType, 'tma')) {
                    DB::statement("ALTER TABLE support_messages MODIFY COLUMN sender ENUM('local', 'tma', 'crm') NOT NULL");
                }
                
                // Теперь обновляем все существующие записи с 'local' на 'tma'
                DB::table('support_messages')
                    ->where('sender', 'local')
                    ->update(['sender' => 'tma']);
                
                // Затем удаляем 'local' из ENUM, оставляя только 'tma' и 'crm'
                DB::statement("ALTER TABLE support_messages MODIFY COLUMN sender ENUM('tma', 'crm') NOT NULL");
            }
        }
    }

    public function down(): void
    {
        // Переименовываем обратно body в message
        if (Schema::hasColumn('support_messages', 'body') && !Schema::hasColumn('support_messages', 'message')) {
            DB::statement('ALTER TABLE support_messages CHANGE body message TEXT NOT NULL');
        }

        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropIndex(['external_message_id']);
            $table->dropColumn('external_message_id');
            DB::statement("ALTER TABLE support_messages MODIFY COLUMN sender ENUM('local', 'crm') NOT NULL");
        });

        // Переименовываем обратно subject в theme
        if (Schema::hasColumn('support_tickets', 'subject')) {
            DB::statement('ALTER TABLE support_tickets CHANGE subject theme VARCHAR(255) NOT NULL');
        }
        
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['external_id']);
            $table->dropColumn('external_id');
        });
    }
};

