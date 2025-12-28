# Руководство по использованию сервиса синхронизации данных TrendAgent

## 📋 Обзор

`TrendDataSyncService` - сервис для синхронизации данных из TrendAgent API в базу данных с полной обработкой ошибок.

## 🚀 Использование

### Базовый пример

```php
use App\Services\TrendDataSyncService;
use App\Services\TrendSsoApiAuth;

// 1. Авторизация
$apiAuth = new TrendSsoApiAuth();
$apiAuth->authenticate($phone, $password);

// 2. Получение данных из API
$apiData = $apiAuth->getBlocksSearch(['count' => 10]);

// 3. Синхронизация данных
$syncService = new TrendDataSyncService();

foreach ($apiData['data']['results'] ?? [] as $blockData) {
    try {
        $block = $syncService->syncBlock($blockData, [
            'create_missing_references' => true, // Создавать города, застройщиков и т.д.
            'update_existing' => true, // Обновлять существующие записи
            'log_errors' => true, // Логировать ошибки
            'skip_errors' => false, // Не пропускать ошибки (бросать исключения)
        ]);
        
        echo "Синхронизирован блок: {$block->name}\n";
        
    } catch (\App\Exceptions\TrendParserException $e) {
        // Ошибка обработана и залогирована
        echo "Ошибка синхронизации: {$e->getMessage()}\n";
    }
}
```

## ⚙️ Опции синхронизации

```php
$options = [
    // Создавать недостающие справочники (города, застройщики и т.д.)
    'create_missing_references' => true,
    
    // Обновлять существующие записи
    'update_existing' => true,
    
    // Логировать ошибки в таблицу parser_errors
    'log_errors' => true,
    
    // Пропускать ошибки (не бросать исключения)
    'skip_errors' => false,
];
```

## 🔍 Обработка ошибок

### Просмотр ошибок

```php
use App\Models\ParserError;

// Все нерешенные ошибки
$errors = ParserError::unresolved()
    ->orderBy('created_at', 'desc')
    ->get();

// Ошибки определенного типа
$apiErrors = ParserError::unresolved()
    ->byType('api')
    ->get();

// Ошибки определенного объекта
$blockErrors = ParserError::unresolved()
    ->byObjectType('block')
    ->get();
```

### Решение ошибок

```php
$error = ParserError::find($id);

// Пометить как решенное
$error->markAsResolved(
    auth()->id(),
    'Проблема исправлена вручную'
);
```

### Статистика ошибок

```php
use App\Http\Controllers\Api\ParserErrorController;

// Через контроллер
GET /api/v1/parser-errors/statistics
```

## 📊 Логирование ошибок

Все ошибки автоматически логируются в таблицу `parser_errors` с детальной информацией:

- Тип ошибки (`api`, `parsing`, `validation`, `database`)
- Тип объекта (`block`, `parking`, `village`, `commercial_block`)
- Детали ошибки (сообщение, код, trace)
- Контекст (данные из API, URL и т.д.)
- HTTP информация (если применимо)
- Пользователь и IP адрес

## 🎯 Рекомендации

1. **Всегда используйте try-catch** при синхронизации
2. **Логируйте ошибки** для последующего анализа
3. **Регулярно проверяйте нерешенные ошибки**
4. **Используйте транзакции** для целостности данных
5. **Валидируйте данные** перед сохранением

## 🔧 Интеграция с парсером

```php
// В TrendSsoController или отдельной команде
public function syncFromApi()
{
    $apiAuth = new TrendSsoApiAuth();
    $syncService = new TrendDataSyncService();
    
    try {
        $apiAuth->authenticate($phone, $password);
        $blocks = $apiAuth->getBlocksSearch(['count' => 100]);
        
        $synced = 0;
        $errors = 0;
        
        foreach ($blocks['data']['results'] ?? [] as $blockData) {
            try {
                $syncService->syncBlock($blockData);
                $synced++;
            } catch (\Exception $e) {
                $errors++;
                // Ошибка уже залогирована
            }
        }
        
        return [
            'synced' => $synced,
            'errors' => $errors,
        ];
        
    } catch (\Exception $e) {
        // Критическая ошибка
        Log::error('Critical sync error', ['error' => $e->getMessage()]);
        throw $e;
    }
}
```

