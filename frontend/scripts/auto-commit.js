#!/usr/bin/env node

/**
 * Автоматический коммит с анализом изменений
 * Анализирует изменения в файлах и создает осмысленное сообщение коммита
 */

import { execSync } from 'child_process';
import { readFileSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const projectRoot = join(__dirname, '..');

// Функция для получения изменений
function getChanges() {
  try {
    const status = execSync('git status --porcelain', { 
      encoding: 'utf-8',
      cwd: projectRoot 
    }).trim();
    
    if (!status) {
      console.log('Нет изменений для коммита');
      return null;
    }

    const diff = execSync('git diff --cached --name-status', { 
      encoding: 'utf-8',
      cwd: projectRoot 
    }).trim();

    const unstaged = execSync('git diff --name-status', { 
      encoding: 'utf-8',
      cwd: projectRoot 
    }).trim();

    return { status, diff, unstaged };
  } catch (error) {
    console.error('Ошибка при получении статуса:', error.message);
    return null;
  }
}

// Функция для анализа изменений и создания сообщения
function generateCommitMessage(changes) {
  const messages = [];
  const files = changes.status.split('\n').map(line => line.trim()).filter(Boolean);
  
  const fileTypes = {
    components: [],
    pages: [],
    styles: [],
    config: [],
    scripts: [],
    docs: [],
    other: []
  };

  files.forEach(file => {
    const path = file.substring(3); // Убираем статус (M, A, D, etc.)
    
    if (path.includes('/components/')) {
      fileTypes.components.push(path);
    } else if (path.includes('/pages/')) {
      fileTypes.pages.push(path);
    } else if (path.match(/\.(css|scss|less)$/)) {
      fileTypes.styles.push(path);
    } else if (path.match(/\.(json|ts|js)$/) && (path.includes('config') || path.includes('vite'))) {
      fileTypes.config.push(path);
    } else if (path.includes('/scripts/')) {
      fileTypes.scripts.push(path);
    } else if (path.match(/\.(md|txt)$/)) {
      fileTypes.docs.push(path);
    } else {
      fileTypes.other.push(path);
    }
  });

  // Генерируем сообщение на основе типов файлов
  if (fileTypes.components.length > 0) {
    const componentNames = fileTypes.components.map(f => {
      const match = f.match(/\/([^/]+)\.(tsx|ts|jsx|js)$/);
      return match ? match[1] : f.split('/').pop();
    });
    
    if (fileTypes.components.length === 1) {
      messages.push(`Обновлен компонент ${componentNames[0]}`);
    } else {
      messages.push(`Обновлены компоненты: ${componentNames.slice(0, 3).join(', ')}${componentNames.length > 3 ? '...' : ''}`);
    }
  }

  if (fileTypes.pages.length > 0) {
    const pageNames = fileTypes.pages.map(f => {
      const match = f.match(/\/([^/]+)\.(tsx|ts|jsx|js)$/);
      return match ? match[1] : f.split('/').pop();
    });
    
    if (pageNames.length === 1) {
      messages.push(`Обновлена страница ${pageNames[0]}`);
    } else {
      messages.push(`Обновлены страницы: ${pageNames.join(', ')}`);
    }
  }

  if (fileTypes.styles.length > 0) {
    messages.push(`Обновлены стили`);
  }

  if (fileTypes.config.length > 0) {
    messages.push(`Обновлена конфигурация`);
  }

  if (fileTypes.scripts.length > 0) {
    messages.push(`Обновлены скрипты`);
  }

  if (fileTypes.docs.length > 0) {
    messages.push(`Обновлена документация`);
  }

  if (fileTypes.other.length > 0) {
    const otherFiles = fileTypes.other.slice(0, 2).map(f => f.split('/').pop());
    messages.push(`Обновлены файлы: ${otherFiles.join(', ')}`);
  }

  // Анализируем содержимое изменений для более детального описания
  const detailedMessages = [];
  
  try {
    const diffContent = execSync('git diff HEAD', { 
      encoding: 'utf-8',
      cwd: projectRoot,
      maxBuffer: 10 * 1024 * 1024 // 10MB
    });

    // Проверяем на конкретные паттерны
    if (diffContent.includes('mobile-first') || diffContent.includes('max-w-screen-sm')) {
      detailedMessages.push('Применен mobile-first подход');
    }
    
    if (diffContent.includes('flex-col')) {
      detailedMessages.push('Использован flex-col layout');
    }
    
    if (diffContent.includes('HeroSection') || diffContent.includes('HeroSearch')) {
      detailedMessages.push('Вынесены компоненты Hero');
    }
    
    if (diffContent.includes('padding') || diffContent.includes('py-') || diffContent.includes('px-')) {
      detailedMessages.push('Оптимизированы отступы');
    }

    if (diffContent.includes('max-w-')) {
      detailedMessages.push('Ограничена ширина контейнеров');
    }

  } catch (error) {
    // Игнорируем ошибки анализа diff
  }

  // Объединяем сообщения
  let commitMessage = messages.join('; ');
  
  if (detailedMessages.length > 0) {
    commitMessage += ' | ' + detailedMessages.join(', ');
  }

  // Добавляем информацию о количестве файлов
  const fileCount = files.length;
  if (fileCount > 1) {
    commitMessage += ` (${fileCount} файлов)`;
  }

  return commitMessage || 'Обновления в проекте';
}

// Основная функция
function main() {
  const changes = getChanges();
  
  if (!changes) {
    return;
  }

  // Добавляем все изменения
  try {
    execSync('git add -A', { 
      cwd: projectRoot,
      stdio: 'inherit'
    });
  } catch (error) {
    console.error('Ошибка при добавлении файлов:', error.message);
    process.exit(1);
  }

  // Генерируем сообщение коммита
  const commitMessage = generateCommitMessage(changes);
  
  console.log('\n📝 Сообщение коммита:', commitMessage);
  console.log('');

  // Создаем коммит
  try {
    execSync(`git commit -m "${commitMessage}"`, { 
      cwd: projectRoot,
      stdio: 'inherit'
    });
    console.log('\n✅ Коммит создан успешно');
  } catch (error) {
    console.error('Ошибка при создании коммита:', error.message);
    process.exit(1);
  }
}

main();

