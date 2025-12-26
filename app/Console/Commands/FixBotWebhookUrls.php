<?php

namespace App\Console\Commands;

use App\Models\Bot;
use Illuminate\Console\Command;

class FixBotWebhookUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bots:fix-webhook-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Исправляет webhook URLs для всех ботов, заменяя токены на ID ботов';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Исправление webhook URLs для всех ботов...');
        
        $bots = Bot::all();
        $fixed = 0;
        
        foreach ($bots as $bot) {
            $correctUrl = url('/api/telegram/webhook/' . $bot->id);
            
            // Проверяем, содержит ли URL токен вместо ID
            $needsFix = false;
            if ($bot->webhook_url !== $correctUrl) {
                $needsFix = true;
            } elseif ($bot->webhook_url && str_contains($bot->webhook_url, $bot->token)) {
                // Если URL содержит токен бота, это неправильно
                $needsFix = true;
            }
            
            if ($needsFix) {
                $this->line("Бот #{$bot->id} ({$bot->name}):");
                $this->line("  Старый URL: " . ($bot->webhook_url ?: 'не установлен'));
                $this->line("  Новый URL: {$correctUrl}");
                
                $bot->webhook_url = $correctUrl;
                $bot->save();
                
                $fixed++;
                $this->info("  ✅ Исправлено");
            } else {
                $this->line("Бот #{$bot->id} ({$bot->name}): URL уже правильный ({$bot->webhook_url})");
            }
        }
        
        if ($fixed > 0) {
            $this->info("✅ Исправлено {$fixed} ботов");
        } else {
            $this->info("ℹ️ Все боты уже имеют правильные URLs");
        }
        
        return Command::SUCCESS;
    }
}
