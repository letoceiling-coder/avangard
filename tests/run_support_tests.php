<?php

/**
 * Скрипт для выполнения тестов системы поддержки
 * Создает тестовую БД и запускает все тесты
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

echo "=== Настройка тестовой среды ===\n";

// Получаем настройки БД из .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$dbName = $_ENV['DB_DATABASE'] ?? 'tma';
$dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
$dbUser = $_ENV['DB_USERNAME'] ?? 'root';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';

echo "База данных: {$dbName}\n";
echo "Хост: {$dbHost}\n\n";

// Создаем подключение без указания базы данных
try {
    $pdo = new PDO("mysql:host={$dbHost}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Создаем тестовую базу данных если её нет
    $testDbName = $dbName . '_test';
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$testDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Тестовая база данных '{$testDbName}' создана\n";
    
    // Устанавливаем переменную окружения для тестов
    putenv("DB_DATABASE={$testDbName}");
    $_ENV['DB_DATABASE'] = $testDbName;
    
    echo "\n=== Запуск тестов ===\n";
    echo "Выполните: php artisan test --filter Support\n";
    
} catch (PDOException $e) {
    echo "Ошибка подключения к БД: " . $e->getMessage() . "\n";
    exit(1);
}

