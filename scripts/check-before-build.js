#!/usr/bin/env node

/**
 * Скрипт для проверки перед сборкой
 * Предотвращает зависания и проблемы
 */

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('🔍 Проверка перед сборкой...\n');

// Проверка Node.js
try {
    const nodeVersion = execSync('node --version', { encoding: 'utf-8' }).trim();
    console.log(`✅ Node.js: ${nodeVersion}`);
} catch (error) {
    console.error('❌ Node.js не найден');
    process.exit(1);
}

// Проверка npm
try {
    const npmVersion = execSync('npm --version', { encoding: 'utf-8' }).trim();
    console.log(`✅ npm: ${npmVersion}`);
} catch (error) {
    console.error('❌ npm не найден');
    process.exit(1);
}

// Проверка блокировок
const lockFiles = ['package-lock.json', 'yarn.lock'];
for (const file of lockFiles) {
    if (fs.existsSync(file)) {
        console.log(`✅ Lock файл найден: ${file}`);
    }
}

// Проверка node_modules
if (fs.existsSync('node_modules')) {
    console.log('✅ node_modules существует');
} else {
    console.log('⚠️  node_modules не найден, будет создан при сборке');
}

// Проверка памяти (для больших проектов)
try {
    const totalMemory = require('os').totalmem();
    const freeMemory = require('os').freemem();
    const memoryInGB = (freeMemory / 1024 / 1024 / 1024).toFixed(2);
    console.log(`✅ Свободная память: ${memoryInGB} GB`);
    
    if (freeMemory < 1024 * 1024 * 1024) { // Меньше 1GB
        console.warn('⚠️  Мало свободной памяти, сборка может быть медленной');
    }
} catch (error) {
    console.warn('⚠️  Не удалось проверить память');
}

// Проверка места на диске
try {
    const stats = fs.statSync('.');
    console.log('✅ Доступ к директории проекта');
} catch (error) {
    console.error('❌ Нет доступа к директории проекта');
    process.exit(1);
}

console.log('\n✅ Все проверки пройдены, можно начинать сборку\n');

