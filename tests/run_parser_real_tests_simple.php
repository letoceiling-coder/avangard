<?php

/**
 * Простой скрипт для выполнения реальных тестов API парсера
 * 
 * Использование: php tests/run_parser_real_tests_simple.php
 */

echo "🧪 Запуск реальных тестов API парсера\n";
echo str_repeat("=", 80) . "\n\n";

// Тестовые сценарии
$testCases = [
    [
        'name' => 'Тест 1: Парсинг blocks для Москвы (базовый)',
        'command' => 'php artisan trend:parse --type=blocks --city=msk --limit=5',
    ],
    [
        'name' => 'Тест 2: Парсинг blocks с проверкой изображений',
        'command' => 'php artisan trend:parse --type=blocks --city=msk --limit=3 --check-images',
    ],
    [
        'name' => 'Тест 3: Парсинг blocks с принудительным обновлением',
        'command' => 'php artisan trend:parse --type=blocks --city=msk --limit=5 --force',
    ],
    [
        'name' => 'Тест 4: Парсинг blocks для СПб (проверка external_id)',
        'command' => 'php artisan trend:parse --type=blocks --city=spb --limit=5',
    ],
    [
        'name' => 'Тест 5: Парсинг commercial-blocks',
        'command' => 'php artisan trend:parse --type=commercial-blocks --city=msk --limit=5',
    ],
    [
        'name' => 'Тест 6: Парсинг parkings',
        'command' => 'php artisan trend:parse --type=parkings --city=msk --limit=5',
    ],
    [
        'name' => 'Тест 7: Парсинг с большим лимитом',
        'command' => 'php artisan trend:parse --type=blocks --city=msk --limit=20',
    ],
    [
        'name' => 'Тест 8: Парсинг с offset',
        'command' => 'php artisan trend:parse --type=blocks --city=msk --limit=10 --offset=10',
    ],
    [
        'name' => 'Тест 9: Парсинг с пропуском ошибок',
        'command' => 'php artisan trend:parse --type=blocks --city=msk --limit=5 --skip-errors',
    ],
];

$results = [];
$errors = [];

foreach ($testCases as $index => $testCase) {
    echo "📋 {$testCase['name']}\n";
    echo "   Команда: {$testCase['command']}\n";
    echo "   Выполнение...\n";
    
    $startTime = microtime(true);
    
    // Выполняем команду
    $output = [];
    $exitCode = 0;
    exec($testCase['command'] . ' 2>&1', $output, $exitCode);
    $outputString = implode("\n", $output);
    
    $executionTime = microtime(true) - $startTime;
    
    $results[] = [
        'test_number' => $index + 1,
        'name' => $testCase['name'],
        'command' => $testCase['command'],
        'success' => $exitCode === 0,
        'exit_code' => $exitCode,
        'execution_time' => round($executionTime, 2),
        'output' => $outputString,
    ];
    
    if ($exitCode === 0) {
        echo "   ✅ Успешно за {$results[count($results) - 1]['execution_time']} сек\n";
        
        // Показываем статистику из вывода
        $outputLines = explode("\n", $outputString);
        $statsLines = array_filter($outputLines, function($line) {
            return stripos($line, '📊') !== false || 
                   stripos($line, '✅') !== false ||
                   stripos($line, 'Всего') !== false ||
                   stripos($line, 'Создано') !== false ||
                   stripos($line, 'Обновлено') !== false;
        });
        if (!empty($statsLines)) {
            echo "   Статистика:\n";
            foreach (array_slice($statsLines, 0, 5) as $line) {
                echo "      " . trim($line) . "\n";
            }
        }
    } else {
        echo "   ❌ Ошибка (код: {$exitCode}, время: {$results[count($results) - 1]['execution_time']} сек)\n";
        $errors[] = $testCase['name'];
        
        // Показываем ошибки из вывода
        $outputLines = explode("\n", $outputString);
        $errorLines = array_filter($outputLines, function($line) {
            return stripos($line, 'error') !== false || 
                   stripos($line, 'ошибка') !== false ||
                   stripos($line, '❌') !== false ||
                   stripos($line, 'MongoID') !== false ||
                   stripos($line, '400') !== false ||
                   stripos($line, '500') !== false;
        });
        if (!empty($errorLines)) {
            echo "   Релевантные сообщения:\n";
            foreach (array_slice($errorLines, 0, 5) as $line) {
                echo "      " . trim($line) . "\n";
            }
        }
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
    
    echo "Основные проблемы:\n";
    foreach ($results as $result) {
        if (!$result['success']) {
            $outputLines = explode("\n", $result['output']);
            $errorLines = array_filter($outputLines, function($line) {
                return stripos($line, 'MongoID') !== false ||
                       stripos($line, '400') !== false ||
                       stripos($line, '500') !== false ||
                       stripos($line, 'external_id') !== false;
            });
            if (!empty($errorLines)) {
                echo "\n  {$result['name']}:\n";
                foreach (array_slice($errorLines, 0, 3) as $line) {
                    echo "    " . trim($line) . "\n";
                }
            }
        }
    }
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
    $reportContent .= "Команда: {$result['command']}\n";
    $reportContent .= "Статус: $status\n";
    $reportContent .= "Время: {$result['execution_time']} сек\n";
    $reportContent .= "Код выхода: {$result['exit_code']}\n";
    $reportContent .= "\nВывод:\n{$result['output']}\n";
    $reportContent .= str_repeat("-", 80) . "\n\n";
}

file_put_contents($reportFile, $reportContent);
echo "📄 Подробный отчет сохранен в: $reportFile\n\n";

if ($failedTests === 0) {
    echo "🎉 ВСЕ ТЕСТЫ ПРОШЛИ УСПЕШНО!\n";
} else {
    echo "⚠️  Обнаружены проблемы в $failedTests тестах\n";
    echo "\n";
    echo "💡 Рекомендации:\n";
    echo "   - Для blocks API требуется external_id (MongoDB ObjectId) для городов\n";
    echo "   - Проверьте, что города имеют заполненное поле external_id в БД\n";
    echo "   - Некоторые API могут не поддерживать все города\n";
}

