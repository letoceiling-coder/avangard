import{_ as e,c as s,a as i,F as g,q as a,o as r,p as d,t as p}from"./admin-DaIM1wig.js";const c={name:"Documentation",data(){return{activeSection:"overview",sections:[{id:"overview",title:"Обзор системы"},{id:"api",title:"API Endpoints"},{id:"admin",title:"Админ-панель"},{id:"deployment",title:"Деплой"}],documentation:{overview:`
                    <h2>Обзор системы</h2>
                    <p>Backend система для Telegram Mini App приложений на базе Laravel 11.</p>

                    <h3>Основные возможности</h3>
                    <ul>
                        <li><strong>Система авторизации</strong> - Sanctum токены, регистрация, восстановление пароля</li>
                        <li><strong>Админ-панель</strong> - Полнофункциональная панель управления на Vue 3</li>
                        <li><strong>Медиа-библиотека</strong> - Организация и управление файлами с поддержкой папок</li>
                        <li><strong>Система уведомлений</strong> - Встроенная система уведомлений для пользователей</li>
                        <li><strong>Система поддержки</strong> - Тикеты и чат с интеграцией внешних CRM</li>
                        <li><strong>Управление пользователями и ролями</strong> - Гибкая система прав доступа</li>
                        <li><strong>Проверка подписки</strong> - Интеграция с внешними системами управления подписками</li>
                        <li><strong>Автоматический деплой</strong> - GitHub Actions, webhooks, Git hooks</li>
                        <li><strong>Telegram интеграция</strong> - Пакет letoceiling-coder/telegram для работы с Telegram Bot API</li>
                    </ul>

                    <h3>Технологический стек</h3>
                    <ul>
                        <li><strong>Backend:</strong> Laravel 11, PHP 8.2+</li>
                        <li><strong>Frontend (Админ):</strong> Vue 3, TypeScript, Vite</li>
                        <li><strong>База данных:</strong> MySQL/PostgreSQL</li>
                        <li><strong>Авторизация:</strong> Laravel Sanctum</li>
                        <li><strong>Интеграции:</strong> Telegram Bot API</li>
                    </ul>
                `,api:`
                    <h2>API Endpoints</h2>
                    <p>Все API endpoints используют префикс <code>/api</code>.</p>

                    <h3>Авторизация</h3>
                    <ul>
                        <li><strong>POST /api/auth/register</strong> - Регистрация нового пользователя</li>
                        <li><strong>POST /api/auth/login</strong> - Вход в систему</li>
                        <li><strong>POST /api/auth/logout</strong> - Выход (требует авторизации)</li>
                        <li><strong>GET /api/auth/user</strong> - Получение данных текущего пользователя</li>
                        <li><strong>POST /api/auth/forgot-password</strong> - Запрос на восстановление пароля</li>
                        <li><strong>POST /api/auth/reset-password</strong> - Сброс пароля</li>
                    </ul>

                    <h3>Уведомления</h3>
                    <ul>
                        <li><strong>GET /api/notifications</strong> - Список непрочитанных уведомлений</li>
                        <li><strong>GET /api/notifications/all</strong> - Все уведомления</li>
                        <li><strong>POST /api/notifications/{id}/read</strong> - Отметить как прочитанное</li>
                        <li><strong>DELETE /api/notifications/{id}</strong> - Удалить уведомление</li>
                        <li><strong>GET /api/notifications/unread-count</strong> - Количество непрочитанных</li>
                    </ul>

                    <h3>Медиа-библиотека (v1)</h3>
                    <ul>
                        <li><strong>GET /api/v1/folders</strong> - Список папок</li>
                        <li><strong>POST /api/v1/folders</strong> - Создать папку</li>
                        <li><strong>PUT /api/v1/folders/{id}</strong> - Обновить папку</li>
                        <li><strong>DELETE /api/v1/folders/{id}</strong> - Удалить папку</li>
                        <li><strong>GET /api/v1/folders/tree/all</strong> - Дерево всех папок</li>
                        <li><strong>POST /api/v1/folders/update-positions</strong> - Обновить позиции папок</li>
                        <li><strong>POST /api/v1/folders/{id}/restore</strong> - Восстановить из корзины</li>
                        <li><strong>GET /api/v1/media</strong> - Список медиа-файлов</li>
                        <li><strong>POST /api/v1/media</strong> - Загрузить файл</li>
                        <li><strong>PUT /api/v1/media/{id}</strong> - Обновить медиа</li>
                        <li><strong>DELETE /api/v1/media/{id}</strong> - Удалить медиа</li>
                        <li><strong>POST /api/v1/media/{id}/restore</strong> - Восстановить из корзины</li>
                        <li><strong>DELETE /api/v1/media/trash/empty</strong> - Очистить корзину</li>
                    </ul>

                    <h3>Пользователи и роли (только для admin)</h3>
                    <ul>
                        <li><strong>GET /api/v1/users</strong> - Список пользователей</li>
                        <li><strong>POST /api/v1/users</strong> - Создать пользователя</li>
                        <li><strong>PUT /api/v1/users/{id}</strong> - Обновить пользователя</li>
                        <li><strong>DELETE /api/v1/users/{id}</strong> - Удалить пользователя</li>
                        <li><strong>GET /api/v1/roles</strong> - Список ролей</li>
                        <li><strong>POST /api/v1/roles</strong> - Создать роль</li>
                        <li><strong>PUT /api/v1/roles/{id}</strong> - Обновить роль</li>
                        <li><strong>DELETE /api/v1/roles/{id}</strong> - Удалить роль</li>
                    </ul>

                    <h3>Система поддержки (только для admin)</h3>
                    <ul>
                        <li><strong>GET /api/v1/support/tickets</strong> - Список тикетов</li>
                        <li><strong>GET /api/v1/support/tickets/{id}</strong> - Детали тикета</li>
                        <li><strong>POST /api/v1/support/ticket</strong> - Создать тикет</li>
                        <li><strong>POST /api/v1/support/message</strong> - Отправить сообщение в тикет</li>
                    </ul>

                    <h3>Интеграции (защищено deploy.token)</h3>
                    <ul>
                        <li><strong>POST /api/integration/messages</strong> - Получить сообщение от внешней системы</li>
                        <li><strong>POST /api/integration/status</strong> - Изменить статус от внешней системы</li>
                        <li><strong>POST /api/support/webhook/message</strong> - Webhook сообщения (legacy)</li>
                        <li><strong>POST /api/support/webhook/status</strong> - Webhook статуса (legacy)</li>
                    </ul>

                    <h3>Деплой (защищено deploy.token)</h3>
                    <ul>
                        <li><strong>POST /api/deploy</strong> - Выполнить деплой на сервере</li>
                        <li><strong>POST /api/seed</strong> - Запустить seeders</li>
                        <li><strong>POST /api/webhook/github</strong> - Webhook от GitHub для автоматического деплоя</li>
                    </ul>

                    <h3>Публичные endpoints</h3>
                    <ul>
                        <li><strong>GET /api/subscription/check</strong> - Проверка статуса подписки</li>
                        <li><strong>GET /api/logs</strong> - Просмотр логов</li>
                        <li><strong>GET /api/logs/files</strong> - Список файлов логов</li>
                        <li><strong>POST /api/logs/clear</strong> - Очистка логов</li>
                    </ul>

                    <h3>Админ-панель</h3>
                    <ul>
                        <li><strong>GET /api/admin/menu</strong> - Получить меню админ-панели (с фильтрацией по ролям)</li>
                    </ul>
                `,admin:`
                    <h2>Админ-панель</h2>
                    <p>Админ-панель доступна по адресу <code>/admin</code> и требует авторизации.</p>

                    <h3>Структура меню</h3>
                    <ul>
                        <li><strong>Медиа</strong> - Управление медиа-файлами (изображения, документы, видео)</li>
                        <li><strong>Уведомления</strong> - Просмотр и управление уведомлениями</li>
                        <li><strong>Пользователи</strong> - Управление пользователями системы (только admin)</li>
                        <li><strong>Роли</strong> - Управление ролями и правами доступа (только admin)</li>
                        <li><strong>Подписка</strong> - Информация о статусе подписки</li>
                        <li><strong>Поддержка</strong> - Система тикетов и чата</li>
                        <li><strong>Документация</strong> - Техническая документация</li>
                    </ul>

                    <h3>Права доступа</h3>
                    <ul>
                        <li><strong>admin</strong> - Полный доступ ко всем разделам</li>
                        <li><strong>manager</strong> - Доступ к Медиа, Уведомлениям, Поддержке, Подписке, Документации</li>
                        <li><strong>user</strong> - Доступ только к Документации</li>
                    </ul>

                    <h3>Медиа-библиотека</h3>
                    <ul>
                        <li>Организация файлов в папки</li>
                        <li>Поддержка различных типов файлов (изображения, документы, видео)</li>
                        <li>Корзина для удаленных файлов</li>
                        <li>Загрузка файлов через drag & drop</li>
                        <li>Фильтрация и поиск</li>
                    </ul>

                    <h3>Система поддержки</h3>
                    <ul>
                        <li>Создание тикетов с прикреплением файлов</li>
                        <li>Обмен сообщениями в тикетах</li>
                        <li>Интеграция с внешними CRM системами</li>
                        <li>Webhooks для получения сообщений от внешних систем</li>
                    </ul>
                `,deployment:`
                    <h2>Автоматический деплой</h2>
                    <p>Система поддерживает автоматическое развертывание кода на сервере через несколько методов.</p>

                    <h3>GitHub Actions</h3>
                    <p>При каждом push в ветку <code>main</code> автоматически запускается деплой на сервер.</p>
                    <ul>
                        <li>Workflow файл: <code>.github/workflows/deploy.yml</code></li>
                        <li>Игнорирует изменения в <code>*.md</code>, <code>.gitignore</code>, <code>.editorconfig</code></li>
                        <li>Использует Secrets: <code>DEPLOY_SERVER_URL</code> и <code>DEPLOY_TOKEN</code></li>
                    </ul>

                    <h3>GitHub Webhook</h3>
                    <p>Endpoint <code>/api/webhook/github</code> обрабатывает push события от GitHub.</p>
                    <ul>
                        <li>Проверяет подпись webhook (если настроен <code>GITHUB_WEBHOOK_SECRET</code>)</li>
                        <li>Или проверяет токен <code>DEPLOY_TOKEN</code></li>
                        <li>Деплоит только из ветки <code>main</code></li>
                    </ul>

                    <h3>Процесс деплоя</h3>
                    <ol>
                        <li>Обновление кода из репозитория (<code>git pull</code>)</li>
                        <li>Установка зависимостей (<code>composer install</code>)</li>
                        <li>Выполнение миграций (<code>php artisan migrate</code>)</li>
                        <li>Очистка кешей</li>
                        <li>Оптимизация приложения</li>
                    </ol>

                    <h3>Настройка</h3>
                    <p>В <code>.env</code> файле настройте:</p>
                    <ul>
                        <li><code>DEPLOY_TOKEN</code> - Токен для авторизации деплоя</li>
                        <li><code>DEPLOY_SERVER_URL</code> - URL сервера (опционально, для локальных деплоев)</li>
                    </ul>
                `}}},computed:{currentContent(){return this.documentation[this.activeSection]||"<p>Раздел в разработке</p>"}}},u={class:"documentation-page"},h={class:"space-y-6"},m={class:"bg-card rounded-lg border border-border p-4"},T={class:"flex flex-wrap gap-2"},E=["onClick"],v={class:"bg-card rounded-lg border border-border p-6"},P=["innerHTML"];function S(b,o,O,f,l,n){return r(),s("div",u,[o[1]||(o[1]=i("div",{class:"mb-6"},[i("h1",{class:"text-2xl font-bold text-foreground"},"Документация"),i("p",{class:"text-muted-foreground mt-1"},"Техническая документация системы")],-1)),i("div",h,[i("div",m,[o[0]||(o[0]=i("h2",{class:"text-lg font-semibold mb-4"},"Разделы документации",-1)),i("nav",T,[(r(!0),s(g,null,a(l.sections,t=>(r(),s("button",{key:t.id,onClick:_=>l.activeSection=t.id,class:d(["px-4 py-2 rounded-lg text-sm font-medium transition-colors",l.activeSection===t.id?"bg-primary text-primary-foreground":"bg-muted text-muted-foreground hover:bg-muted/80"])},p(t.title),11,E))),128))])]),i("div",v,[i("div",{innerHTML:n.currentContent,class:"prose prose-sm max-w-none dark:prose-invert"},null,8,P)])])])}const G=e(c,[["render",S],["__scopeId","data-v-270aa390"]]);export{G as default};
