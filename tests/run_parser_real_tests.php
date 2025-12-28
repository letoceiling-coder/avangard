<?php

/**
 * Скрипт для выполнения реальных тестов API парсера
 * 
 * Использование: php tests/run_parser_real_tests.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;

echo "🧪 Запуск реальных тестов API парсера\n";
echo str_repeat("=", 80) . "\n\n";

// Получаем credentials из env или используем значения по умолчанию из команды
$phone = env('TREND_SSO_PHONE', '+79045393434');
$password = env('TREND_SSO_PASSWORD', 'nwBvh4q');

if (empty($phone) || empty($password)) {
    echo "❌ Ошибка: TREND_SSO_PHONE и TREND_SSO_PASSWORD должны быть настроены\n";
    echo "Добавьте в .env или используйте значения по умолчанию:\n";
    echo "TREND_SSO_PHONE=+79045393434\n";
    echo "TREND_SSO_PASSWORD=nwBvh4q\n";
    exit(1);
}

echo "✅ Используемые credentials\n";
echo "   Phone: " . substr($phone, 0, 5) . "***\n";
echo "   Password: " . (strlen($password) > 0 ? str_repeat('*', strlen($password)) : 'не задан') . "\n\n";

// Тестовые сценарии
$testCases = [
    [
        'name' => 'Тест 1: Парсинг blocks для Москвы (базовый)',
        'command' => 'trend:parse',
        'args' => [
            '--type' => 'blocks',
            '--city' => 'msk',
            '--limit' => 5,
        ],
    ],
    [
        'name' => 'Тест 2: Парсинг blocks с проверкой изображений',
        'command' => 'trend:parse',
        'args' => [
            '--type' => 'blocks',
            '--city' => 'msk',
            '--limit' => 3,
            '--check-images' => true,
        ],
    ],
    [
        'name' => 'Тест 3: Парсинг blocks с принудительным обновлением',
        'command' => 'trend:parse',
        'args' => [
            '--type' => 'blocks',
            '--city' => 'msk',
            '--limit' => 5,
            '--force' => true,
        ],
    ],
    [
        'name' => 'Тест 4: Парсинг blocks для СПб (проверка external_id)',
        'command' => 'trend:parse',
        'args' => [
            '--type' => 'blocks',
            '--city' => 'spb',
            '--limit' => 5,
        ],
    ],
    [
        'name' => 'Тест 5: Парсинг commercial-blocks',
        'command' => 'trend:parse',
        'args' => [
            '--type' => 'commercial-blocks',
            '--city' => 'msk',
            '--limit' => 5,
        ],
    ],
    [
        'name' => 'Тест 6: Парсинг нескольких типов (с пропуском ошибок)',
        'command' => 'trend:parse',
        'args' => [
            '--type' => ['blocks', 'parkings'],
            '--city' => 'msk',
            '--limit' => 3,
            '--skip-errors' => true,
        ],
    ],
    [
        'name' => 'Тест 7: Парсинг с большим лимитом',
        'command' => 'trend:parse',
        'args' => [
            '--type' => 'blocks',
            '--city' => 'msk',
            '--limit' => 20,
        ],
    ],
    [
        'name' => 'Тест 8: Парсинг с offset',
        'command' => 'trend:parse',
        'args' => [
            '--type' => 'blocks',
            '--city' => 'msk',
            '--limit' => 10,
            '--offset' => 10,
        ],
    ],
];

$results = [];
$errors = [];

foreach ($testCases as $index => $testCase) {
    echo "📋 {$testCase['name']}\n";
    
    // Формируем строку команды для вывода
    $cmdParts = [];
    foreach ($testCase['args'] as $key => $value) {
        $key = str_replace('_', '-', $key);
        if (is_bool($value) && $value) {
            $cmdParts[] = "--{$key}";
        } elseif (is_array($value)) {
            foreach ($value as $v) {
                $cmdParts[] = "--{$key}={$v}";
            }
        } else {
            $cmdParts[] = "--{$key}={$value}";
        }
    }
    echo "   Команда: php artisan {$testCase['command']} " . implode(' ', $cmdParts) . "\n";
    echo "   Выполнение...\n";
    
    $startTime = microtime(true);
    
    try {
        // Добавляем credentials в args
        $args = array_merge($testCase['args'], [
            '--phone' => $phone,
            '--password' => $password,
        ]);
        
        // Убеждаемся, что массивы передаются правильно
        // Artisan::call ожидает, что множественные значения уже в массиве
        $exitCode = Artisan::call($testCase['command'], $args);
        $output = Artisan::output();
        
        $executionTime = microtime(true) - $startTime;
        
        $results[] = [
            'test_number' => $index + 1,
            'name' => $testCase['name'],
            'success' => $exitCode === 0,
            'exit_code' => $exitCode,
            'execution_time' => round($executionTime, 2),
            'output' => $output,
        ];
        
        if ($exitCode === 0) {
            echo "   ✅ Успешно за {$results[count($results) - 1]['execution_time']} сек\n";
        } else {
            echo "   ❌ Ошибка (код: {$exitCode}, время: {$results[count($results) - 1]['execution_time']} сек)\n";
            $errors[] = $testCase['name'];
            
            // Показываем первые строки ошибки
            $outputLines = explode("\n", $output);
            $errorLines = array_filter($outputLines, function($line) {
                return stripos($line, 'error') !== false || 
                       stripos($line, 'ошибка') !== false ||
                       stripos($line, '❌') !== false;
            });
            if (!empty($errorLines)) {
                echo "   Релевантные сообщения:\n";
                foreach (array_slice($errorLines, 0, 3) as $line) {
                    echo "      " . trim($line) . "\n";
                }
            }
        }
    } catch (\Exception $e) {
        $executionTime = microtime(true) - $startTime;
        
        $results[] = [
            'test_number' => $index + 1,
            'name' => $testCase['name'],
            'success' => false,
            'exit_code' => -1,
            'execution_time' => round($executionTime, 2),
            'output' => '',
            'error' => $e->getMessage(),
        ];
        
        echo "   ❌ Исключение: " . substr($e->getMessage(), 0, 100) . "...\n";
        $errors[] = $testCase['name'];
    }
    
    echo "\n";
    
    // Пауза между тестами
    sleep(2);
}

// Формируем отчет
echo str_repeat("=", 80) . "\n";
echo "📊 ИТОГОВЫЙ ОТЧЕТ\n";
echo str_repeat("=", 80) . "\n\n";

$totalTests = count($results);
$successfulTests = count(array_filter($results, fn($r) => $r['success']));
$failedTests = $totalTests - $successfulTests;
$totalExecutionTime = array_sum(array_column($results, 'execution_time'));

echo "Всего тестов: $totalTests\n";
echo "Успешных: $successfulTests ✅\n";
echo "Проваленных: $failedTests ❌\n";
echo "Общее время выполнения: " . round($totalExecutionTime, 2) . " сек\n";
echo "\n";

if ($failedTests > 0) {
    echo "Проваленные тесты:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
}

// Сохраняем подробный отчет
$reportFile = 'tests/parser_real_api_test_report_' . date('Y-m-d_H-i-s') . '.txt';
$reportContent = "ОТЧЕТ О РЕАЛЬНЫХ ТЕСТАХ API ПАРСЕРА\n";
$reportContent .= "Дата: " . date('Y-m-d H:i:s') . "\n";
$reportContent .= str_repeat("=", 80) . "\n\n";
$reportContent .= "ИТОГИ:\n";
$reportContent .= "Всего тестов: $totalTests\n";
$reportContent .= "Успешных: $successfulTests\n";
$reportContent .= "Проваленных: $failedTests\n";
$reportContent .= "Общее время: " . round($totalExecutionTime, 2) . " сек\n\n";

$reportContent .= "ДЕТАЛЬНЫЙ ОТЧЕТ:\n";
$reportContent .= str_repeat("-", 80) . "\n\n";

foreach ($results as $result) {
    $status = $result['success'] ? 'УСПЕШНО' : 'ПРОВАЛЕНО';
    $reportContent .= "Тест #{$result['test_number']}: {$result['name']}\n";
    $reportContent .= "Статус: $status\n";
    $reportContent .= "Время: {$result['execution_time']} сек\n";
    $reportContent .= "Код выхода: {$result['exit_code']}\n";
    
    if (!empty($result['error'])) {
        $reportContent .= "\nОшибка:\n{$result['error']}\n";
    }
    
    $reportContent .= "\nВывод:\n{$result['output']}\n";
    $reportContent .= str_repeat("-", 80) . "\n\n";
}

file_put_contents($reportFile, $reportContent);
echo "📄 Подробный отчет сохранен в: $reportFile\n\n";

if ($failedTests === 0) {
    echo "🎉 ВСЕ ТЕСТЫ ПРОШЛИ УСПЕШНО!\n";
} else {
    echo "⚠️  Обнаружены проблемы в $failedTests тестах\n";
}

