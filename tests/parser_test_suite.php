<?php

/**
 * Скрипт для тестирования команды парсера с разными вариантами
 * 
 * Использование: php tests/parser_test_suite.php
 */

$baseCommand = 'php artisan trend:parse';
$testResults = [];
$errors = [];

echo "🧪 Начало тестирования команды парсера\n";
echo str_repeat("=", 80) . "\n\n";

// Определяем тестовые сценарии
$testCases = [
    [
        'name' => 'Тест 1: Парсинг blocks для Москвы (минимальный лимит)',
        'command' => "$baseCommand --type=blocks --city=msk --limit=5",
        'expected' => 'Должен успешно выполниться',
    ],
    [
        'name' => 'Тест 2: Парсинг blocks для Санкт-Петербурга (минимальный лимит)',
        'command' => "$baseCommand --type=blocks --city=spb --limit=5",
        'expected' => 'Должен успешно выполниться с параметром show_type',
    ],
    [
        'name' => 'Тест 3: Парсинг blocks с проверкой изображений',
        'command' => "$baseCommand --type=blocks --city=msk --limit=3 --check-images",
        'expected' => 'Должен успешно выполниться с проверкой изображений',
    ],
    [
        'name' => 'Тест 4: Парсинг нескольких типов для Москвы',
        'command' => "$baseCommand --type=blocks --type=villages --city=msk --limit=3",
        'expected' => 'Должен выполнить парсинг blocks и villages',
    ],
    [
        'name' => 'Тест 5: Парсинг blocks с принудительным обновлением',
        'command' => "$baseCommand --type=blocks --city=msk --limit=3 --force",
        'expected' => 'Должен выполнить принудительное обновление',
    ],
    [
        'name' => 'Тест 6: Парсинг blocks с пропуском ошибок',
        'command' => "$baseCommand --type=blocks --city=msk --limit=3 --skip-errors",
        'expected' => 'Должен продолжать работу при ошибках',
    ],
    [
        'name' => 'Тест 7: Парсинг commercial-blocks для Москвы',
        'command' => "$baseCommand --type=commercial-blocks --city=msk --limit=3",
        'expected' => 'Должен успешно выполниться с параметром show_type',
    ],
];

// Функция для выполнения команды и получения результата
function executeCommand($command, $timeout = 120) {
    $descriptorspec = [
        0 => ['pipe', 'r'],  // stdin
        1 => ['pipe', 'w'],  // stdout
        2 => ['pipe', 'w'],  // stderr
    ];

    $process = proc_open($command, $descriptorspec, $pipes);
    
    if (!is_resource($process)) {
        return [
            'success' => false,
            'output' => '',
            'error' => 'Не удалось создать процесс',
            'exit_code' => -1,
            'execution_time' => 0,
        ];
    }

    $startTime = microtime(true);
    
    // Читаем вывод
    $output = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    
    // Закрываем каналы
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    
    // Получаем статус завершения
    $status = proc_get_status($process);
    proc_close($process);
    
    $executionTime = microtime(true) - $startTime;
    
    return [
        'success' => $status['exitcode'] === 0,
        'output' => $output,
        'error' => $error,
        'exit_code' => $status['exitcode'],
        'execution_time' => round($executionTime, 2),
    ];
}

// Выполняем тесты
foreach ($testCases as $index => $testCase) {
    echo "📋 {$testCase['name']}\n";
    echo "   Команда: {$testCase['command']}\n";
    echo "   Ожидание: {$testCase['expected']}\n";
    echo "   Выполнение...\n";
    
    $result = executeCommand($testCase['command']);
    
    $testResults[] = [
        'test_number' => $index + 1,
        'name' => $testCase['name'],
        'command' => $testCase['command'],
        'expected' => $testCase['expected'],
        'success' => $result['success'],
        'exit_code' => $result['exit_code'],
        'execution_time' => $result['execution_time'],
        'output' => $result['output'],
        'error' => $result['error'],
    ];
    
    if ($result['success']) {
        echo "   ✅ Успешно выполнено за {$result['execution_time']} сек\n";
    } else {
        echo "   ❌ Ошибка (код: {$result['exit_code']}, время: {$result['execution_time']} сек)\n";
        if (!empty($result['error'])) {
            echo "   Ошибка: " . substr($result['error'], 0, 200) . "...\n";
        }
        $errors[] = $testCase['name'];
    }
    
    echo "\n";
    
    // Небольшая пауза между тестами
    sleep(2);
}

// Формируем отчет
echo str_repeat("=", 80) . "\n";
echo "📊 ИТОГОВЫЙ ОТЧЕТ\n";
echo str_repeat("=", 80) . "\n\n";

$totalTests = count($testResults);
$successfulTests = count(array_filter($testResults, fn($r) => $r['success']));
$failedTests = $totalTests - $successfulTests;
$totalExecutionTime = array_sum(array_column($testResults, 'execution_time'));

echo "Всего тестов: $totalTests\n";
echo "Успешных: $successfulTests ✅\n";
echo "Проваленных: $failedTests ❌\n";
echo "Общее время выполнения: " . round($totalExecutionTime, 2) . " сек\n";
echo "\n";

// Детальный отчет по каждому тесту
echo "ДЕТАЛЬНЫЙ ОТЧЕТ:\n";
echo str_repeat("-", 80) . "\n\n";

foreach ($testResults as $result) {
    $status = $result['success'] ? '✅ УСПЕШНО' : '❌ ПРОВАЛЕНО';
    echo "Тест #{$result['test_number']}: {$result['name']}\n";
    echo "   Статус: $status\n";
    echo "   Команда: {$result['command']}\n";
    echo "   Время выполнения: {$result['execution_time']} сек\n";
    echo "   Код выхода: {$result['exit_code']}\n";
    
    if (!$result['success']) {
        if (!empty($result['error'])) {
            echo "   Ошибка:\n";
            $errorLines = explode("\n", trim($result['error']));
            foreach (array_slice($errorLines, 0, 5) as $line) {
                echo "      " . trim($line) . "\n";
            }
            if (count($errorLines) > 5) {
                echo "      ... (еще " . (count($errorLines) - 5) . " строк)\n";
            }
        }
        
        // Пытаемся найти полезную информацию в выводе
        if (!empty($result['output'])) {
            $outputLines = explode("\n", $result['output']);
            $relevantLines = array_filter($outputLines, function($line) {
                return stripos($line, 'error') !== false || 
                       stripos($line, 'ошибка') !== false ||
                       stripos($line, '❌') !== false;
            });
            if (!empty($relevantLines)) {
                echo "   Релевантный вывод:\n";
                foreach (array_slice($relevantLines, 0, 3) as $line) {
                    echo "      " . trim($line) . "\n";
                }
            }
        }
    } else {
        // Для успешных тестов показываем статистику
        if (!empty($result['output'])) {
            $outputLines = explode("\n", $result['output']);
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
        }
    }
    
    echo "\n";
}

// Сохраняем отчет в файл
$reportFile = 'tests/parser_test_report_' . date('Y-m-d_H-i-s') . '.txt';
$reportContent = "ОТЧЕТ О ТЕСТИРОВАНИИ КОМАНДЫ ПАРСЕРА\n";
$reportContent .= "Дата: " . date('Y-m-d H:i:s') . "\n";
$reportContent .= str_repeat("=", 80) . "\n\n";
$reportContent .= "ИТОГИ:\n";
$reportContent .= "Всего тестов: $totalTests\n";
$reportContent .= "Успешных: $successfulTests\n";
$reportContent .= "Проваленных: $failedTests\n";
$reportContent .= "Общее время выполнения: " . round($totalExecutionTime, 2) . " сек\n\n";

$reportContent .= "ДЕТАЛЬНЫЙ ОТЧЕТ:\n";
$reportContent .= str_repeat("-", 80) . "\n\n";

foreach ($testResults as $result) {
    $status = $result['success'] ? 'УСПЕШНО' : 'ПРОВАЛЕНО';
    $reportContent .= "Тест #{$result['test_number']}: {$result['name']}\n";
    $reportContent .= "Статус: $status\n";
    $reportContent .= "Команда: {$result['command']}\n";
    $reportContent .= "Время: {$result['execution_time']} сек\n";
    $reportContent .= "Код выхода: {$result['exit_code']}\n";
    
    if (!$result['success']) {
        $reportContent .= "\nВывод ошибки:\n";
        $reportContent .= $result['error'] . "\n";
    }
    
    $reportContent .= "\nВывод:\n";
    $reportContent .= $result['output'] . "\n";
    $reportContent .= str_repeat("-", 80) . "\n\n";
}

file_put_contents($reportFile, $reportContent);
echo "📄 Подробный отчет сохранен в: $reportFile\n\n";

// Итоговая статистика
echo str_repeat("=", 80) . "\n";
if ($failedTests === 0) {
    echo "🎉 ВСЕ ТЕСТЫ ПРОШЛИ УСПЕШНО!\n";
} else {
    echo "⚠️  Обнаружены проблемы в " . $failedTests . " тестах\n";
    echo "\nПроблемные тесты:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}
echo str_repeat("=", 80) . "\n";

