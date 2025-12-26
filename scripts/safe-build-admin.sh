#!/bin/bash
# Безопасная сборка админ-панели с обработкой ошибок

set -e

echo "Starting admin build..."

# Проверяем наличие Node.js
if ! command -v node &> /dev/null; then
    echo "ERROR: Node.js not found in PATH"
    exit 1
fi

# Переходим в директорию проекта
cd "$(dirname "$0")/.."

# Выполняем сборку с таймаутом (5 минут)
echo "Running: npm run build:admin"
timeout 300 npm run build:admin || {
    exit_code=$?
    echo "Build failed or timed out with exit code: $exit_code"
    exit $exit_code
}

echo "Build completed successfully"

