<?php

/**
 * Скрипт для диагностики проблем с выполнением команд
 */

echo "🔍 Диагностика окружения...\n\n";

// Проверка PHP
echo "PHP:\n";
echo "  Версия: " . phpversion() . "\n";
echo "  Путь: " . (function_exists('php_ini_loaded_file') ? php_ini_loaded_file() : 'не определен') . "\n";
echo "\n";

// Проверка Node.js
echo "Node.js:\n";
$nodeVersion = shell_exec('node --version 2>&1');
echo "  Версия: " . trim($nodeVersion ?: 'не установлен') . "\n";
$npmVersion = shell_exec('npm --version 2>&1');
echo "  npm версия: " . trim($npmVersion ?: 'не установлен') . "\n";
echo "\n";

// Проверка Git
echo "Git:\n";
$gitVersion = shell_exec('git --version 2>&1');
echo "  Версия: " . trim($gitVersion ?: 'не установлен') . "\n";
$gitRepo = is_dir('.git') ? 'да' : 'нет';
echo "  Git репозиторий: {$gitRepo}\n";
echo "\n";

// Проверка процессов
echo "Процессы Node.js:\n";
$nodeProcesses = shell_exec('tasklist /FI "IMAGENAME eq node.exe" 2>NUL | find /C "node.exe"');
echo "  Запущено процессов: " . trim($nodeProcesses ?: '0') . "\n";
echo "\n";

// Проверка блокировок
echo "Файлы блокировок:\n";
$lockFiles = [
    'package-lock.json',
    'yarn.lock',
    'composer.lock',
];
foreach ($lockFiles as $file) {
    $exists = file_exists($file) ? 'да' : 'нет';
    echo "  {$file}: {$exists}\n";
}
echo "\n";

// Проверка прав доступа
echo "Права доступа к ключевым директориям:\n";
$dirs = ['node_modules', 'vendor', 'public/build', 'public/frontend', '.git'];
foreach ($dirs as $dir) {
    $exists = is_dir($dir) || is_file($dir) ? 'да' : 'нет';
    $readable = is_readable($dir) ? 'чтение: да' : 'чтение: нет';
    $writable = is_writable($dir) ? 'запись: да' : 'запись: нет';
    echo "  {$dir}: существует={$exists}, {$readable}, {$writable}\n";
}
echo "\n";

echo "✅ Диагностика завершена\n";

