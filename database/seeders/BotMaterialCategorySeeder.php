<?php

namespace Database\Seeders;

use App\Models\Bot;
use App\Models\BotMaterialCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BotMaterialCategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     * 
     * Создает категории материалов для всех существующих ботов
     */
    public function run(): void
    {
        // Создаем категории для всех существующих ботов
        $bots = Bot::all();
        
        if ($bots->isEmpty()) {
            // Если ботов нет, просто выходим (можно создать бота позже через админ-панель)
            return;
        }

        foreach ($bots as $bot) {
            $this->createCategoriesForBot($bot);
        }
    }

    /**
     * Создать категории материалов для бота
     */
    protected function createCategoriesForBot(Bot $bot): void
    {
        $categories = [
            [
                'name' => 'Структурирование',
                'description' => 'Для построения эффективной юридической и финансовой структуры бизнеса.',
                'order_index' => 1,
            ],
            [
                'name' => 'Партнёрство',
                'description' => 'Материалы по работе с партнёрствами и совместными проектами.',
                'order_index' => 2,
            ],
            [
                'name' => 'Проверки',
                'description' => 'Подготовка и сопровождение проверок контролирующих органов.',
                'order_index' => 3,
            ],
            [
                'name' => 'Наследование',
                'description' => 'Вопросы наследования бизнеса и активов.',
                'order_index' => 4,
            ],
            [
                'name' => 'Ликвидация',
                'description' => 'Процедуры ликвидации юридических лиц.',
                'order_index' => 5,
            ],
            [
                'name' => 'Банкротство',
                'description' => 'Процедуры банкротства и финансового оздоровления.',
                'order_index' => 6,
            ],
            [
                'name' => 'Работа с задолженностями',
                'description' => 'Взыскание и управление задолженностями.',
                'order_index' => 7,
            ],
            [
                'name' => 'Защита активов',
                'description' => 'Стратегии защиты активов от рисков и взысканий.',
                'order_index' => 8,
            ],
        ];

        foreach ($categories as $categoryData) {
            BotMaterialCategory::firstOrCreate(
                [
                    'bot_id' => $bot->id,
                    'name' => $categoryData['name'],
                ],
                [
                    'description' => $categoryData['description'],
                    'order_index' => $categoryData['order_index'],
                    'is_active' => true,
                ]
            );
        }
    }
}

