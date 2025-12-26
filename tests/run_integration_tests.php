<?php

/**
 * Скрипт для выполнения интеграционных тестов с реальным CRM
 * Использует настройки из .env
 */

echo "=== Интеграционные тесты системы поддержки ===\n\n";

// Проверяем наличие необходимых переменных
$required = ['DB_DATABASE', 'DB_HOST', 'DB_USERNAME', 'DEPLOY_TOKEN'];
$missing = [];

foreach ($required as $var) {
    if (empty($_ENV[$var] ?? getenv($var))) {
        $missing[] = $var;
    }
}

if (!empty($missing)) {
    echo "❌ Отсутствуют переменные окружения: " . implode(', ', $missing) . "\n";
    echo "Убедитесь что они установлены в .env файле\n";
    exit(1);
}

echo "✓ Все необходимые переменные окружения найдены\n";
echo "База данных: " . ($_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE')) . "\n";
echo "CRM URL: " . ($_ENV['APP_CRM_URL'] ?? getenv('APP_CRM_URL') ?: 'https://crm.siteaccess.ru/api/v1/tecket') . "\n\n";

echo "Запуск тестов...\n\n";

// Запускаем тесты
exec('php artisan test --filter Support', $output, $returnCode);

foreach ($output as $line) {
    echo $line . "\n";
}

exit($returnCode);

