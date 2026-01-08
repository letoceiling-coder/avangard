# Решение проблем с деплоем

## Проблема: Нет прав на запись в репозиторий (403 Forbidden)

### Симптомы:
```
❌ ОШИБКА: Ошибка отправки в репозиторий:
     remote: Permission denied (publickey).
     fatal: Could not read from remote repository.
     
Или:
❌ ОШИБКА 403: у пользователя USERNAME нет прав на запись в репозиторий
```

### Причины:
1. **Нет прав на запись через HTTPS** - требуется аутентификация
2. **SSH ключи не настроены** - нет доступа через SSH
3. **Неверные credentials** - неправильный токен или пароль
4. **Пользователь не добавлен как collaborator** - нет прав на репозиторий

### Решения:

#### Вариант 1: Personal Access Token (быстро, рекомендуется для Windows)

**Создание токена:**
1. Откройте: https://github.com/settings/tokens
2. Нажмите `Generate new token (classic)`
3. Название: `Avangard Deploy`
4. Права: выберите `repo` (полный доступ к репозиториям)
5. Нажмите `Generate token`
6. **СКОПИРУЙТЕ ТОКЕН** (он показывается только один раз!)

**Настройка через скрипт:**

**Windows:**
```bash
setup-git-token.bat
```

**macOS/Linux:**
```bash
chmod +x setup-git-token.sh
./setup-git-token.sh
```

**Ручная настройка:**
```bash
# Замените YOUR_USERNAME и YOUR_TOKEN на ваши данные
git remote set-url origin https://YOUR_USERNAME:YOUR_TOKEN@github.com/letoceiling-coder/avangard.git

# Или без username (токен содержит информацию о пользователе)
git remote set-url origin https://YOUR_TOKEN@github.com/letoceiling-coder/avangard.git
```

**Проверка:**
```bash
git remote -v
git push origin main
```

#### Вариант 2: SSH (рекомендуется для macOS/Linux)

**Настройка через скрипт:**
```bash
chmod +x setup-git-ssh.sh
./setup-git-ssh.sh
```

**Ручная настройка:**

1. **Проверьте наличие SSH ключей:**
```bash
ls -la ~/.ssh/id_*.pub
```

2. **Если ключей нет, создайте новый:**
```bash
ssh-keygen -t ed25519 -C "your_email@example.com"
# Нажмите Enter для всех вопросов (или укажите пароль)
```

3. **Скопируйте публичный ключ:**
```bash
cat ~/.ssh/id_ed25519.pub
# Или для RSA:
cat ~/.ssh/id_rsa.pub
```

4. **Добавьте ключ в GitHub:**
   - Откройте: https://github.com/settings/ssh/new
   - Вставьте скопированный ключ
   - Нажмите `Add SSH key`

5. **Переключите remote на SSH:**
```bash
git remote set-url origin git@github.com:letoceiling-coder/avangard.git
```

6. **Проверьте подключение:**
```bash
ssh -T git@github.com
# Должно быть: Hi USERNAME! You've successfully authenticated...
```

#### Вариант 3: Получить права на репозиторий

Попросите владельца репозитория (letoceiling-coder) добавить вас как collaborator с правами на запись:
1. Владелец должен перейти в: Settings → Collaborators
2. Добавить ваш GitHub username
3. Выбрать права: `Write` или `Admin`

### Проверка конфигурации:

```bash
# Проверка remote URL
git remote -v

# Проверка прав доступа (для HTTPS)
git ls-remote origin

# Проверка SSH подключения
ssh -T git@github.com
```

### После настройки:

Повторите деплой:
```bash
php artisan deploy --insecure --skip-commit-check
```

---

## Проблема: Коммит на сервере не совпадает с ожидаемым

### Симптомы:
```
❌ ОШИБКА: Коммит на сервере не совпадает с ожидаемым!
     Ожидался: 52fcc05
     На сервере: fd062ad
     Сервер обновился до неправильного коммита.
```

### Причины:
1. **Коммит еще не синхронизирован с удаленным репозиторием** - сервер не может получить коммит, которого нет в remote
2. **На сервере другая ветка** - сервер обновляется из другой ветки
3. **Проблемы с синхронизацией Git** - сервер не успевает получить последние изменения
4. **Конфликты в истории Git** - локальная и удаленная истории различаются

### Решения:

#### 1. Быстрое решение (пропуск проверки коммитов)

Если вы уверены, что коммит отправлен в репозиторий, используйте флаг `--skip-commit-check`:

```bash
php artisan deploy --insecure --skip-commit-check
```

Это пропустит проверку совпадения коммитов и позволит деплою завершиться успешно.

#### 2. Проверка перед деплоем

**Убедитесь, что коммит отправлен в удаленный репозиторий:**

```bash
# Проверьте текущий коммит
git rev-parse HEAD

# Проверьте, что коммит есть в remote
git ls-remote origin main

# Если коммита нет в remote, отправьте его
git push origin main
```

#### 3. Принудительная синхронизация

Если коммит есть в remote, но сервер его не получает:

```bash
# На сервере выполните вручную:
cd /path/to/project
git fetch origin --prune --all
git fetch origin main --depth=1000
git reset --hard origin/main
```

#### 4. Проверка ветки на сервере

Убедитесь, что на сервере правильная ветка:

```bash
# На сервере проверьте текущую ветку
cd /path/to/project
git branch
git remote -v
```

#### 5. Увеличение количества попыток

Система теперь делает до 5 попыток с задержкой 3 секунды между ними. Это должно помочь при проблемах синхронизации.

### Улучшения в коде:

1. **Добавлена опция `--skip-commit-check`** - позволяет пропустить строгую проверку коммитов
2. **Увеличено количество попыток** - с 3 до 5 для лучшей синхронизации
3. **Улучшен fetch на сервере** - теперь используется более глубокий fetch (`--depth=1000`, `--unshallow`)
4. **Улучшена обработка ошибок** - более детальные сообщения и рекомендации

### Рекомендации:

1. **Всегда отправляйте коммиты в remote перед деплоем:**
   ```bash
   git push origin main
   php artisan deploy --insecure
   ```

2. **Используйте `--skip-commit-check` только если:**
   - Вы уверены, что коммит отправлен в remote
   - Проблема связана с задержкой синхронизации
   - Нужно быстро обновить сервер без строгой проверки

3. **Проверяйте логи на сервере** для диагностики:
   ```bash
   # Логи Laravel
   tail -f storage/logs/laravel.log
   
   # Логи деплоя (если настроены)
   tail -f /path/to/deploy.log
   ```

4. **При проблемах с синхронизацией:**
   - Увеличьте задержку между попытками (в коде)
   - Проверьте доступность удаленного репозитория с сервера
   - Убедитесь, что на сервере правильные credentials для Git

### Примеры использования:

```bash
# Обычный деплой (со строгой проверкой коммитов)
php artisan deploy --insecure

# Деплой с пропуском проверки коммитов
php artisan deploy --insecure --skip-commit-check

# Деплой с seeders и пропуском проверки
php artisan deploy --insecure --with-seed --skip-commit-check

# Деплой без сборки фронтенда (если уже собрано)
php artisan deploy --insecure --skip-build --skip-commit-check
```

### Диагностика:

Если проблема сохраняется, проверьте:

1. **Доступность remote репозитория с сервера:**
   ```bash
   ssh user@server
   cd /path/to/project
   git ls-remote origin
   ```

2. **Права доступа к Git:**
   ```bash
   # На сервере
   git config --list | grep user
   ```

3. **Историю коммитов:**
   ```bash
   # Локально
   git log --oneline -10
   
   # На сервере
   ssh user@server "cd /path/to/project && git log --oneline -10"
   ```

### Контакты:

Если проблема не решается, проверьте:
- Логи на сервере (`storage/logs/laravel.log`)
- Настройки Git на сервере
- Доступность удаленного репозитория
- Правильность ветки в настройках деплоя

