<template>
    <div class="documentation-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Документация</h1>
            <p class="text-muted-foreground mt-1">Техническая документация по системе WOW Рулетка</p>
        </div>

        <div class="space-y-6">
            <!-- Навигация по разделам -->
            <div class="bg-card rounded-lg border border-border p-4">
                <h2 class="text-lg font-semibold mb-4">Разделы документации</h2>
                <nav class="flex flex-wrap gap-2">
                    <button
                        v-for="section in sections"
                        :key="section.id"
                        @click="activeSection = section.id"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                            activeSection === section.id
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground hover:bg-muted/80'
                        ]"
                    >
                        {{ section.title }}
                    </button>
                </nav>
            </div>

            <!-- Содержимое документации -->
            <div class="bg-card rounded-lg border border-border p-6">
                <div v-html="currentContent" class="prose prose-sm max-w-none dark:prose-invert"></div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Documentation',
    data() {
        return {
            activeSection: 'application',
            sections: [
                { id: 'application', title: 'Логика приложения' },
                { id: 'database', title: 'База данных' },
                { id: 'api', title: 'API Endpoints' },
                { id: 'admin', title: 'Админ-панель' },
                { id: 'admin-settings', title: 'Настройки админ-панели' },
                { id: 'frontend', title: 'Frontend' },
                { id: 'notifications', title: 'Уведомления' },
                { id: 'commands', title: 'Команды' },
            ],
            documentation: {
                application: `
                    <h2>Логика работы приложения WOW Рулетка</h2>
                    
                    <h3>Общий обзор</h3>
                    <p>WOW Рулетка - это Telegram Mini App, где пользователи могут крутить рулетку и выигрывать призы. Приложение работает на основе билетов, которые восстанавливаются автоматически через определенные интервалы времени.</p>

                    <h3>Жизненный цикл пользователя</h3>
                    <h4>1. Первый запуск приложения</h4>
                    <ol>
                        <li><strong>Проверка подписки на каналы</strong> (<code>GET /api/check-all-subscriptions</code>)
                            <ul>
                                <li>Система проверяет, подписан ли пользователь на все обязательные Telegram-каналы</li>
                                <li>Каналы настраиваются в админ-панели: <code>/admin/wow/channels</code></li>
                                <li>Если пользователь не подписан на все каналы, доступ к рулетке ограничен</li>
                                <li>Проверка выполняется через Telegram Bot API метод <code>getChatMember</code></li>
                            </ul>
                        </li>
                        <li><strong>Инициализация пользователя</strong> (<code>POST /api/user/init</code>)
                            <ul>
                                <li>Создается новая запись в таблице <code>users</code> на основе данных из Telegram</li>
                                <li>Устанавливаются начальные значения:
                                    <ul>
                                        <li><code>tickets_available = 3</code> - начальное количество билетов</li>
                                        <li><code>stars_balance = 0</code> - баланс Telegram Stars</li>
                                        <li><code>total_spins = 0</code> - обнуление статистики</li>
                                        <li><code>total_wins = 0</code> - обнуление выигрышей</li>
                                    </ul>
                                </li>
                                <li>Автоматически обновляются данные из Telegram: имя, username, аватар</li>
                                <li>Если пользователь уже существует, данные обновляются, но билеты и статистика сохраняются</li>
                            </ul>
                        </li>
                        <li><strong>Загрузка конфигурации рулетки</strong> (<code>GET /api/wheel-config</code>)
                            <ul>
                                <li>Получает список всех активных секторов рулетки (12 секторов)</li>
                                <li>Каждый сектор содержит: номер, тип приза, значение приза, иконку, вероятность</li>
                                <li>Вероятности должны суммироваться в 100% (проверяется в админ-панели)</li>
                            </ul>
                        </li>
                        <li><strong>Загрузка билетов пользователя</strong> (<code>GET /api/user/tickets</code>)
                            <ul>
                                <li>Проверяет текущее количество билетов</li>
                                <li>Автоматически восстанавливает билеты, если прошло достаточно времени</li>
                                <li>Возвращает информацию о времени до следующего билета (если билетов меньше 3)</li>
                            </ul>
                        </li>
                    </ol>

                    <h4>2. Процесс прокрута рулетки</h4>
                    <p><strong>Эндпоинт:</strong> <code>POST /api/spin</code></p>
                    <ol>
                        <li><strong>Проверка билетов</strong>
                            <ul>
                                <li>Система проверяет, есть ли у пользователя доступные билеты (<code>tickets_available > 0</code>)</li>
                                <li>Если билетов нет, возвращается ошибка: <code>"No tickets available"</code></li>
                            </ul>
                        </li>
                        <li><strong>Определение выигрышного сектора</strong>
                            <ul>
                                <li><strong>Режим "Всегда пусто"</strong> (настраивается в админ-панели):
                                    <ul>
                                        <li>Если включен <code>always_empty_mode = true</code>, всегда выбирается случайный пустой сектор</li>
                                        <li>Вероятности игнорируются</li>
                                        <li>Полезно для тестирования или временного отключения выигрышей</li>
                                    </ul>
                                </li>
                                <li><strong>Обычный режим</strong>:
                                    <ul>
                                        <li>Выбор сектора происходит на основе вероятностей (<code>probability_percent</code>)</li>
                                        <li>Алгоритм: генерируется случайное число от 0 до суммы всех вероятностей</li>
                                        <li>Сектор выбирается методом кумулятивного распределения</li>
                                        <li>Пример: если сектор имеет вероятность 10%, он выпадет примерно в 10% случаев</li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <li><strong>Списание билета</strong>
                            <ul>
                                <li>Уменьшается <code>tickets_available</code> на 1</li>
                                <li>Обновляется <code>last_spin_at</code> - время последнего прокрута</li>
                                <li>Увеличивается <code>total_spins</code> - счетчик всех прокрутов</li>
                                <li>Если билеты закончились (<code>tickets_available = 0</code>), фиксируется <code>tickets_depleted_at</code> - момент окончания билетов</li>
                            </ul>
                        </li>
                        <li><strong>Создание записи о прокруте</strong>
                            <ul>
                                <li>Создается запись в таблице <code>spins</code> с информацией:
                                    <ul>
                                        <li><code>user_id</code> - пользователь</li>
                                        <li><code>spin_time</code> - время прокрута</li>
                                        <li><code>prize_type</code> - тип приза (money, ticket, secret_box, empty)</li>
                                        <li><code>prize_value</code> - значение приза (для денег: 300, 500, 1000 и т.д.)</li>
                                        <li><code>sector_id</code> - ID сектора</li>
                                        <li><code>sector_number</code> - номер сектора (1-12)</li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <li><strong>Начисление приза</strong>
                            <ul>
                                <li><strong>Пустой сектор</strong> (<code>prize_type = 'empty'</code>):
                                    <ul>
                                        <li>Приз не начисляется</li>
                                        <li>Статистика выигрышей не изменяется</li>
                                    </ul>
                                </li>
                                <li><strong>Денежный приз</strong> (<code>prize_type = 'money'</code>):
                                    <ul>
                                        <li><strong>ВАЖНО:</strong> Деньги НЕ начисляются автоматически в баланс пользователя!</li>
                                        <li>Только увеличивается <code>total_wins</code> - счетчик выигрышей</li>
                                        <li>Приз сохраняется в таблице <code>spins</code> с правильным <code>prize_value</code></li>
                                        <li>Пользователь должен связаться с администратором для получения приза</li>
                                        <li>Администратор может проверить выигрыши в разделе "Победители" (<code>/admin/wow/winners</code>)</li>
                                    </ul>
                                </li>
                                <li><strong>Билет</strong> (<code>prize_type = 'ticket'</code>):
                                    <ul>
                                        <li>Начисляется количество билетов, указанное в <code>prize_value</code> сектора</li>
                                        <li>Увеличивается <code>tickets_available</code> на значение <code>prize_value</code></li>
                                        <li>Если билеты были 0, сбрасывается <code>tickets_depleted_at</code></li>
                                        <li>Увеличивается <code>total_wins</code></li>
                                    </ul>
                                </li>
                                <li><strong>Секретный бокс / Подарок от спонсора</strong> (<code>prize_type = 'secret_box'</code> или <code>'sponsor_gift'</code>):
                                    <ul>
                                        <li><strong>ВАЖНО:</strong> Приз НЕ начисляется автоматически!</li>
                                        <li>Только увеличивается <code>total_wins</code></li>
                                        <li>Пользователь должен связаться с администратором для получения приза</li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <li><strong>Расчет угла поворота колеса</strong>
                            <ul>
                                <li>Система рассчитывает точный угол поворота для остановки колеса на центре выигрышного сектора</li>
                                <li>Формула: <code>normalizedRotation = 345 - (sectorIndex * 30)</code></li>
                                <li>Добавляется случайное количество полных оборотов (5-10) для визуального эффекта</li>
                                <li>Учитывается количество спинов пользователя для уникальности каждого поворота</li>
                                <li>Финальный угол: <code>rotation = (userSpins * 360 * 20) + (randomSpins * 360) + normalizedRotation</code></li>
                            </ul>
                        </li>
                        <li><strong>Ответ сервера</strong>
                            <ul>
                                <li>Возвращается JSON с информацией:
                                    <ul>
                                        <li><code>spin_id</code> - ID записи о прокруте</li>
                                        <li><code>sector</code> - информация о выигрышном секторе</li>
                                        <li><code>rotation</code> - угол поворота колеса (в градусах)</li>
                                        <li><code>tickets_available</code> - оставшееся количество билетов</li>
                                        <li><code>prize_awarded</code> - был ли начислен приз</li>
                                        <li><code>next_ticket_at</code> - время восстановления следующего билета (если билетов 0)</li>
                                        <li><code>seconds_until_next_ticket</code> - секунд до следующего билета</li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    </ol>

                    <h4>3. Система билетов</h4>
                    <h5>Восстановление билетов</h5>
                    <ul>
                        <li><strong>Автоматическое восстановление</strong>:
                            <ul>
                                <li>Билеты восстанавливаются автоматически через определенный интервал времени</li>
                                <li>Интервал настраивается в админ-панели: <code>ticket_restore_hours</code> (от 1 до 24 часов)</li>
                                <li>По умолчанию: 3 часа</li>
                                <li>Максимальное количество билетов: 3</li>
                            </ul>
                        </li>
                        <li><strong>Логика восстановления</strong>:
                            <ul>
                                <li>Когда билеты заканчиваются (<code>tickets_available = 0</code>), фиксируется <code>tickets_depleted_at</code></li>
                                <li>Восстановление происходит через интервал: <code>tickets_depleted_at + ticket_restore_hours</code></li>
                                <li>При каждом запросе <code>GET /api/user/tickets</code> проверяется, прошло ли достаточно времени</li>
                                <li>Если прошло, восстанавливается 1 билет (до максимума 3)</li>
                                <li>Если билеты были получены другим способом (обмен звёзд, выигрыш), <code>tickets_depleted_at</code> сбрасывается</li>
                            </ul>
                        </li>
                        <li><strong>Команда восстановления</strong>:
                            <ul>
                                <li>Запускается автоматически каждые 3 часа через cron: <code>php artisan wow:restore-tickets</code></li>
                                <li>Находит всех пользователей с билетами меньше 3</li>
                                <li>Проверяет, прошло ли достаточно времени с последнего прокрута</li>
                                <li>Восстанавливает 1 билет и отправляет уведомление</li>
                            </ul>
                        </li>
                    </ul>

                    <h4>4. Обмен Telegram Stars на билеты</h4>
                    <p><strong>Эндпоинт:</strong> <code>POST /api/stars/exchange</code></p>
                    <ol>
                        <li><strong>Инициация обмена</strong>
                            <ul>
                                <li>Пользователь выбирает количество звёзд для обмена (обычно 50 звёзд)</li>
                                <li>Получает 20 билетов за 50 звёзд</li>
                                <li>Создается запись в таблице <code>star_exchanges</code> со статусом <code>pending</code></li>
                            </ul>
                        </li>
                        <li><strong>Подтверждение обмена</strong>
                            <ul>
                                <li>После успешной оплаты через Telegram Stars Invoice</li>
                                <li>Telegram отправляет webhook или фронтенд вызывает <code>POST /api/stars/exchange/confirm</code></li>
                                <li>Статус обмена меняется на <code>completed</code></li>
                                <li>Начисляются билеты пользователю: <code>tickets_available += 20</code></li>
                                <li>Увеличивается баланс звёзд: <code>stars_balance += 50</code></li>
                                <li>Создается запись в <code>user_tickets</code> с источником <code>star_exchange</code></li>
                            </ul>
                        </li>
                    </ol>

                    <h4>5. Реферальная система</h4>
                    <ul>
                        <li><strong>Получение реферальной ссылки</strong> (<code>GET /api/referral/link</code>):
                            <ul>
                                <li>Пользователь получает уникальную ссылку: <code>{app_url}?ref={telegram_id}</code></li>
                                <li>Ссылка сохраняется и может быть использована для приглашения друзей</li>
                            </ul>
                        </li>
                        <li><strong>Регистрация по реферальной ссылке</strong> (<code>POST /api/referral/register</code>):
                            <ul>
                                <li>При первом запуске приложения проверяется параметр <code>ref</code> в URL</li>
                                <li>Если найден, создается запись в таблице <code>referrals</code></li>
                                <li>Устанавливается <code>invited_by</code> у нового пользователя</li>
                                <li>Проверяется, что пользователь не приглашает сам себя</li>
                            </ul>
                        </li>
                        <li><strong>Лидерборд</strong>:
                            <ul>
                                <li>Рейтинг формируется на основе количества приглашений за текущий месяц</li>
                                <li>Призы начисляются за первые 3 места (настраивается в админ-панели)</li>
                                <li>Призы: 1 место - 1500₽, 2 место - 1000₽, 3 место - 500₽ (настраивается)</li>
                            </ul>
                        </li>
                    </ul>

                    <h3>Как изменить логику приложения из админ-панели</h3>
                    <p>Все основные настройки приложения можно изменить через админ-панель без изменения кода:</p>
                    <ul>
                        <li><strong>Вероятности выпадения призов</strong> - раздел "Рулетка" (<code>/admin/wow/wheel</code>)</li>
                        <li><strong>Типы призов и их значения</strong> - раздел "Типы призов" (<code>/admin/wow/prize-types</code>)</li>
                        <li><strong>Интервал восстановления билетов</strong> - раздел "Рулетка" → "Настройки рулетки"</li>
                        <li><strong>Режим "Всегда пусто"</strong> - раздел "Рулетка" → "Настройки рулетки"</li>
                        <li><strong>Обязательные каналы для подписки</strong> - раздел "Каналы" (<code>/admin/wow/channels</code>)</li>
                        <li><strong>Призы лидерборда</strong> - раздел "Призы лидерборда" (<code>/admin/wow/leaderboard</code>)</li>
                    </ul>
                    <p>Подробнее о каждом разделе см. в разделе "Настройки админ-панели".</p>
                `,
                database: `
                    <h2>Структура базы данных</h2>
                    <h3>Таблица: users</h3>
                    <p>Расширенная таблица пользователей с поддержкой Telegram:</p>
                    <ul>
                        <li><strong>telegram_id</strong> (bigint, unique) - ID пользователя в Telegram</li>
                        <li><strong>username</strong> (string) - Username пользователя</li>
                        <li><strong>avatar_url</strong> (string) - URL аватара</li>
                        <li><strong>stars_balance</strong> (integer, default: 0) - Баланс Telegram Stars</li>
                        <li><strong>tickets_available</strong> (integer, default: 0) - Доступные билеты для прокрута</li>
                        <li><strong>last_spin_at</strong> (timestamp) - Время последнего прокрута</li>
                        <li><strong>invited_by</strong> (foreign key to users) - Кто пригласил пользователя</li>
                        <li><strong>total_spins</strong> (integer, default: 0) - Всего прокрутов</li>
                        <li><strong>total_wins</strong> (integer, default: 0) - Всего выигрышей</li>
                    </ul>

                    <h3>Таблица: channels</h3>
                    <p>Обязательные Telegram-каналы для подписки:</p>
                    <ul>
                        <li><strong>username</strong> (string, unique) - @username канала</li>
                        <li><strong>title</strong> (string) - Название канала</li>
                        <li><strong>is_active</strong> (boolean, default: true) - Активен ли канал</li>
                        <li><strong>priority</strong> (integer, default: 0) - Приоритет для сортировки</li>
                    </ul>

                    <h3>Таблица: wheel_sectors</h3>
                    <p>Конфигурация секторов рулетки:</p>
                    <ul>
                        <li><strong>sector_number</strong> (integer, unique, 1-12) - Номер сектора</li>
                        <li><strong>prize_type</strong> (enum: money, ticket, secret_box, empty) - Тип приза</li>
                        <li><strong>prize_value</strong> (integer, default: 0) - Значение приза (для денег: 300, 500, 1000 и т.д.)</li>
                        <li><strong>icon_url</strong> (string, nullable) - URL иконки сектора</li>
                        <li><strong>probability_percent</strong> (decimal 5,2, default: 0) - Вероятность выпадения (0-100%)</li>
                        <li><strong>is_active</strong> (boolean, default: true) - Активен ли сектор</li>
                    </ul>
                    <p><strong>Важно:</strong> Сумма всех probability_percent должна быть равна 100%.</p>

                    <h3>Таблица: wheel_settings</h3>
                    <p>Глобальные настройки рулетки:</p>
                    <ul>
                        <li><strong>always_empty_mode</strong> (boolean, default: false) - Режим "всегда пусто" (колесо всегда останавливается на пустом секторе)</li>
                        <li><strong>ticket_restore_hours</strong> (integer, default: 3) - Период восстановления билета в часах (от 1 до 24)</li>
                    </ul>
                    <p><strong>Примечание:</strong> В таблице всегда одна запись (id = 1). Настройки можно изменить через админ панель.</p>

                    <h3>Таблица: spins</h3>
                    <p>История прокрутов рулетки:</p>
                    <ul>
                        <li><strong>user_id</strong> (foreign key to users) - Пользователь</li>
                        <li><strong>spin_time</strong> (timestamp) - Время прокрута</li>
                        <li><strong>prize_type</strong> (enum) - Тип выигранного приза</li>
                        <li><strong>prize_value</strong> (integer) - Значение приза</li>
                        <li><strong>sector_id</strong> (foreign key to wheel_sectors) - Сектор, на котором остановилось колесо</li>
                    </ul>

                    <h3>Таблица: referrals</h3>
                    <p>Реферальные связи между пользователями:</p>
                    <ul>
                        <li><strong>inviter_id</strong> (foreign key to users) - Кто пригласил</li>
                        <li><strong>invited_id</strong> (foreign key to users) - Кого пригласили</li>
                        <li><strong>invited_at</strong> (timestamp) - Когда был приглашен</li>
                    </ul>
                    <p><strong>Ограничение:</strong> Уникальная пара (inviter_id, invited_id) - один пользователь не может быть приглашен одним человеком дважды.</p>

                    <h3>Таблица: user_tickets</h3>
                    <p>История билетов пользователей:</p>
                    <ul>
                        <li><strong>user_id</strong> (foreign key to users) - Пользователь</li>
                        <li><strong>tickets_count</strong> (integer, default: 1) - Количество билетов</li>
                        <li><strong>restored_at</strong> (timestamp, nullable) - Время восстановления</li>
                        <li><strong>source</strong> (enum: free, star_exchange, admin_grant) - Источник билета</li>
                    </ul>

                    <h3>Таблица: star_exchanges</h3>
                    <p>История обмена Telegram Stars на билеты:</p>
                    <ul>
                        <li><strong>user_id</strong> (foreign key to users) - Пользователь</li>
                        <li><strong>stars_amount</strong> (integer) - Количество потраченных звёзд</li>
                        <li><strong>tickets_received</strong> (integer) - Полученных билетов (обычно 20)</li>
                        <li><strong>transaction_id</strong> (string, unique, nullable) - ID транзакции от Telegram</li>
                        <li><strong>status</strong> (enum: pending, completed, failed) - Статус транзакции</li>
                    </ul>

                    <h3>Таблица: leaderboard_snapshots</h3>
                    <p>Снимки лидерборда за каждый месяц:</p>
                    <ul>
                        <li><strong>user_id</strong> (foreign key to users) - Пользователь</li>
                        <li><strong>month</strong> (integer, 1-12) - Месяц</li>
                        <li><strong>year</strong> (integer) - Год</li>
                        <li><strong>invites_count</strong> (integer, default: 0) - Количество приглашений</li>
                        <li><strong>rank</strong> (integer, nullable) - Позиция в рейтинге</li>
                        <li><strong>prize_amount</strong> (integer, default: 0) - Размер приза</li>
                        <li><strong>prize_paid</strong> (boolean, default: false) - Выплачен ли приз</li>
                    </ul>
                    <p><strong>Ограничение:</strong> Уникальная пара (user_id, month, year).</p>

                    <h3>Seeder для начальных данных</h3>
                    <p>Для создания 12 секторов рулетки с начальными настройками выполните:</p>
                    <pre><code>php artisan db:seed --class=WheelSectorSeeder</code></pre>
                    <p>Seeder создаст 12 секторов с распределением вероятностей:</p>
                    <ul>
                        <li>7 секторов "Пусто" - 63% вероятности</li>
                        <li>4 сектора с денежными призами (300₽, 500₽, 1000₽) - 26% вероятности</li>
                        <li>1 сектор с билетом - 5% вероятности</li>
                        <li>Общая вероятность: 100%</li>
                    </ul>
                `,
                api: `
                    <h2>API Endpoints</h2>
                    <p>Все API endpoints используют префикс <code>/api</code>. Для защиты используется middleware <code>telegram.initdata</code>, который проверяет валидность Telegram initData.</p>

                    <h3>Проверка подписки на каналы</h3>
                    <ul>
                        <li><strong>GET /api/check-subscription/{channelUsername}</strong> - Проверка подписки на конкретный канал
                            <ul>
                                <li>Требует: заголовок <code>X-Telegram-Init-Data</code> или query параметр <code>initData</code></li>
                                <li>Возвращает: <code>{is_subscribed: bool, status: string}</code></li>
                            </ul>
                        </li>
                        <li><strong>GET /api/check-all-subscriptions</strong> - Проверка подписки на все обязательные каналы
                            <ul>
                                <li>Возвращает: <code>{all_subscribed: bool, channels: array}</code></li>
                                <li>Каждый канал содержит: <code>username</code>, <code>title</code>, <code>is_subscribed</code>, <code>status</code></li>
                            </ul>
                        </li>
                    </ul>

                    <h3>Инициализация пользователя</h3>
                    <ul>
                        <li><strong>POST /api/user/init</strong> - Инициализация/регистрация пользователя WOW при первом запуске Mini App
                            <ul>
                                <li>Требует: заголовок <code>X-Telegram-Init-Data</code> или query параметр <code>initData</code></li>
                                <li>Создает нового пользователя или обновляет существующего на основе данных из Telegram</li>
                                <li>Устанавливает начальные значения: 3 билета, 0 звёзд, обнуляет статистику</li>
                                <li>Автоматически обновляет данные из Telegram: имя, username, аватар</li>
                                <li>Возвращает: <code>{success: bool, user: object, is_new_user: bool}</code></li>
                                <li><strong>Рекомендуется вызывать</strong> сразу после успешной проверки подписки на каналы</li>
                                <li>Альтернатива: пользователь автоматически создается при первом запросе к <code>/api/user/tickets</code> или <code>/api/spin</code></li>
                            </ul>
                        </li>
                    </ul>
                    <p><strong>Процесс запуска приложения:</strong></p>
                    <ol>
                        <li>Пользователь открывает Mini App в Telegram</li>
                        <li>Проверка подписки на обязательные каналы (<code>/api/check-all-subscriptions</code>)</li>
                        <li>Инициализация пользователя (<code>/api/user/init</code>) - создание/обновление записи в БД</li>
                        <li>Загрузка конфигурации рулетки (<code>/api/wheel-config</code>)</li>
                        <li>Загрузка билетов пользователя (<code>/api/user/tickets</code>)</li>
                    </ol>

                    <h3>Рулетка</h3>
                    <ul>
                        <li><strong>GET /api/wheel-config</strong> - Получение конфигурации рулетки (публичный доступ)
                            <ul>
                                <li>Возвращает: список секторов с вероятностями и настройками</li>
                                <li>Формат: <code>{sectors: array, total_probability: number}</code></li>
                            </ul>
                        </li>
                        <li><strong>POST /api/spin</strong> - Запуск прокрута рулетки (требует middleware)
                            <ul>
                                <li>Требует: доступный билет (проверяется автоматически)</li>
                                <li>Возвращает: <code>{success: bool, spin_id: int, sector: object, rotation: number, tickets_available: int, prize_awarded: bool}</code></li>
                                <li>Автоматически: уменьшает количество билетов, создает запись в БД, начисляет приз</li>
                            </ul>
                        </li>
                    </ul>

                    <h3>Билеты</h3>
                    <ul>
                        <li><strong>GET /api/user/tickets</strong> - Получение текущего количества билетов
                            <ul>
                                <li>Возвращает: <code>{tickets_available: int, max_tickets: int, last_spin_at: timestamp}</code></li>
                                <li>Автоматически проверяет и восстанавливает билеты (каждые 2-4 часа)</li>
                                <li>Максимум билетов: 3</li>
                            </ul>
                        </li>
                        <li><strong>POST /api/tickets/restore</strong> - Ручное восстановление билетов (админ, в разработке)</li>
                    </ul>

                    <h3>Telegram Stars Exchange</h3>
                    <ul>
                        <li><strong>POST /api/stars/exchange/initiate</strong> - Инициировать обмен звёзд на билеты
                            <ul>
                                <li>Параметры: <code>{stars_amount: int, tickets_amount: int}</code> (по умолчанию 50 звёзд = 20 билетов)</li>
                                <li>Возвращает: <code>{exchange_id, stars_amount, tickets_amount}</code></li>
                                <li>Создает запись со статусом <code>pending</code></li>
                            </ul>
                        </li>
                        <li><strong>POST /api/stars/exchange/confirm</strong> - Подтверждение обмена (после успешной оплаты)
                            <ul>
                                <li>Параметры: <code>{exchange_id: int, transaction_id: string}</code></li>
                                <li>Начисляет билеты пользователю, обновляет статус на <code>completed</code></li>
                                <li>Создает запись в <code>user_tickets</code> с источником <code>star_exchange</code></li>
                            </ul>
                        </li>
                        <li><strong>GET /api/stars/exchange/history</strong> - История обменов пользователя
                            <ul>
                                <li>Возвращает последние 50 обменов</li>
                            </ul>
                        </li>
                        <li><strong>POST /api/stars/exchange/webhook</strong> - Webhook от Telegram (обработка транзакций)
                            <ul>
                                <li>Вызывается автоматически Telegram после подтверждения оплаты</li>
                                <li>Обрабатывает транзакцию и начисляет билеты</li>
                            </ul>
                        </li>
                    </ul>
                    <p><strong>Процесс обмена:</strong></p>
                    <ol>
                        <li>Фронтенд вызывает <code>initiateExchange</code> → получает <code>exchange_id</code></li>
                        <li>Фронтенд открывает Telegram Stars Invoice через <code>window.Telegram.WebApp.openInvoice()</code></li>
                        <li>Пользователь подтверждает оплату в Telegram</li>
                        <li>Telegram отправляет webhook → обрабатывается автоматически, или фронтенд вызывает <code>confirmExchange</code></li>
                        <li>Билеты начисляются пользователю</li>
                    </ol>

                    <h3>Реферальная система</h3>
                    <ul>
                        <li><strong>GET /api/referral/link</strong> - Получение реферальной ссылки пользователя
                            <ul>
                                <li>Возвращает: <code>{referral_link: string, telegram_id: int}</code></li>
                                <li>Формат ссылки: <code>{app_url}?ref={telegram_id}</code></li>
                            </ul>
                        </li>
                        <li><strong>POST /api/referral/register</strong> - Регистрация по реферальной ссылке
                            <ul>
                                <li>Параметры: <code>{referrer_id: int}</code> - telegram_id пригласившего</li>
                                <li>Создает запись в таблице <code>referrals</code></li>
                                <li>Обновляет <code>invited_by</code> у пользователя</li>
                                <li>Проверяет, что пользователь не приглашает сам себя</li>
                            </ul>
                        </li>
                        <li><strong>GET /api/referral/stats</strong> - Статистика приглашений пользователя
                            <ul>
                                <li>Возвращает: <code>{total_invites: int, current_month_invites: int}</code></li>
                            </ul>
                        </li>
                    </ul>

                    <h3>Лидерборд</h3>
                    <ul>
                        <li><strong>GET /api/leaderboard</strong> - Получение ТОП пользователей по приглашениям
                            <ul>
                                <li>Параметры: <code>month</code> (1-12, по умолчанию текущий), <code>year</code> (по умолчанию текущий), <code>limit</code> (по умолчанию 10, максимум 50)</li>
                                <li>Возвращает: <code>{leaderboard: array, month: int, year: int}</code></li>
                                <li>Каждый элемент содержит: <code>rank</code>, <code>telegram_id</code>, <code>username</code>, <code>avatar_url</code>, <code>invites_count</code>, <code>prize_amount</code></li>
                                <li>Призы: 1 место - 1500₽, 2 место - 1000₽, 3 место - 500₽</li>
                            </ul>
                        </li>
                    </ul>

                    <h3>Telegram Service</h3>
                    <p>Сервис <code>App\Services\TelegramService</code> предоставляет следующие методы:</p>
                    <ul>
                        <li><strong>validateInitData(string $initData, string $botToken): bool</strong> - Валидация подписи initData</li>
                        <li><strong>parseInitData(string $initData): ?array</strong> - Парсинг initData и извлечение данных</li>
                        <li><strong>getTelegramId(string $initData): ?int</strong> - Получение telegram_id из initData</li>
                    </ul>

                    <h3>Middleware</h3>
                    <ul>
                        <li><strong>telegram.initdata</strong> - Проверяет наличие и валидность Telegram initData
                            <ul>
                                <li>В режиме разработки (<code>APP_DEBUG=true</code>) разрешает запросы без валидации</li>
                                <li>Добавляет <code>telegram_init_data</code> в request для использования в контроллерах</li>
                            </ul>
                        </li>
                    </ul>
                `,
                admin: `
                    <h2>Админ-панель - Обзор</h2>
                    <p>Админ-панель доступна по адресу <code>/admin</code> и требует авторизации. Доступ предоставляется пользователям с ролями <code>admin</code> или <code>manager</code>.</p>

                    <h3>Структура меню</h3>
                    <ul>
                        <li><strong>Медиа</strong> - Управление медиа-файлами (изображения, иконки)</li>
                        <li><strong>Уведомления</strong> - Просмотр и управление уведомлениями</li>
                        <li><strong>Пользователи</strong> - Управление пользователями системы (только admin)</li>
                        <li><strong>Роли</strong> - Управление ролями и правами доступа (только admin)</li>
                        <li><strong>WOW Рулетка</strong> - Все разделы, связанные с рулеткой:
                            <ul>
                                <li>Каналы</li>
                                <li>Рулетка</li>
                                <li>Типы призов</li>
                                <li>Пользователи WOW</li>
                                <li>Рефералы</li>
                                <li>Статистика</li>
                                <li>Призы лидерборда</li>
                                <li>Победители</li>
                            </ul>
                        </li>
                        <li><strong>Конфигурации</strong> - Настройки системы:
                            <ul>
                                <li>Бот</li>
                            </ul>
                        </li>
                        <li><strong>Документация</strong> - Техническая документация (текущий раздел)</li>
                    </ul>

                    <h3>Права доступа</h3>
                    <ul>
                        <li><strong>admin</strong> - Полный доступ ко всем разделам</li>
                        <li><strong>manager</strong> - Доступ к разделам WOW Рулетка, Медиа, Уведомления, Документация</li>
                        <li><strong>user</strong> - Доступ только к Документации</li>
                    </ul>

                    <h3>Краткое описание разделов</h3>
                    <p>Подробное описание каждого раздела с примерами и вариантами исполнения см. в разделе <strong>"Настройки админ-панели"</strong>.</p>

                    <h4>WOW Рулетка → Каналы</h4>
                    <p>Управление обязательными Telegram-каналами для подписки. Пользователи должны быть подписаны на все активные каналы для доступа к рулетке.</p>

                    <h4>WOW Рулетка → Рулетка</h4>
                    <p>Главный раздел настройки рулетки. Здесь настраиваются секторы, вероятности выпадения и глобальные настройки (режим "Всегда пусто", интервал восстановления билетов).</p>

                    <h4>WOW Рулетка → Типы призов</h4>
                    <p>Управление типами призов и их действиями. Каждый тип приза может иметь автоматическое действие (например, начисление билетов).</p>

                    <h4>WOW Рулетка → Пользователи WOW</h4>
                    <p>Просмотр всех пользователей рулетки с детальной информацией: баланс билетов и звёзд, статистика прокрутов, история выигрышей, реферальная информация.</p>

                    <h4>WOW Рулетка → Рефералы</h4>
                    <p>Управление реферальной системой. Просмотр всех реферальных связей, статистика приглашений, топ приглашающих пользователей.</p>

                    <h4>WOW Рулетка → Статистика</h4>
                    <p>Аналитика и метрики системы: общее количество прокрутов, распределение призов, активные пользователи, выручка от обменов звёзд.</p>

                    <h4>WOW Рулетка → Призы лидерборда</h4>
                    <p>Настройка призов за места в лидерборде. Можно настроить призы за первые 10 мест, период расчета лидерборда (1-3 месяца).</p>

                    <h4>WOW Рулетка → Победители</h4>
                    <p>Просмотр победителей денежных призов и лидерборда. Управление статусом выплат призов.</p>

                    <h4>Конфигурации → Бот</h4>
                    <p>Настройка Telegram бота: токен, webhook, кнопка меню, тест подключения. Все настройки берутся из <code>.env</code> файла.</p>

                    <h3>Документация</h3>
                    <p>Этот раздел содержит техническую документацию по системе. Документация обновляется автоматически при добавлении новых функций.</p>
                    <p><strong>Совет:</strong> Для подробного описания настроек каждого раздела с примерами перейдите в раздел <strong>"Настройки админ-панели"</strong>.</p>
                `,
                'admin-settings': `
                    <h2>Настройки админ-панели</h2>
                    <p>Админ-панель предоставляет полный контроль над всеми аспектами приложения WOW Рулетка. Все изменения применяются немедленно и влияют на работу пользовательского приложения.</p>

                    <h3>Раздел: WOW Рулетка → Каналы</h3>
                    <p><strong>Путь:</strong> <code>/admin/wow/channels</code></p>
                    <p><strong>Назначение:</strong> Управление обязательными Telegram-каналами для подписки пользователей.</p>
                    
                    <h4>Функционал:</h4>
                    <ul>
                        <li><strong>Добавление канала</strong>:
                            <ul>
                                <li>Нажмите кнопку "Добавить канал"</li>
                                <li>Введите <code>@username</code> канала (без символа @)</li>
                                <li>Введите название канала</li>
                                <li>Установите приоритет (для сортировки в списке)</li>
                                <li>По умолчанию канал активен</li>
                            </ul>
                        </li>
                        <li><strong>Редактирование канала</strong>:
                            <ul>
                                <li>Нажмите на канал в списке</li>
                                <li>Измените username, название или приоритет</li>
                                <li>Сохраните изменения</li>
                            </ul>
                        </li>
                        <li><strong>Активация/деактивация</strong>:
                            <ul>
                                <li>Переключите переключатель "Активен"</li>
                                <li>Неактивные каналы не проверяются при входе пользователя</li>
                                <li>Полезно для временного отключения каналов без удаления</li>
                            </ul>
                        </li>
                        <li><strong>Удаление канала</strong>:
                            <ul>
                                <li>Нажмите кнопку удаления</li>
                                <li>Подтвердите удаление</li>
                                <li>Канал будет удален из базы данных</li>
                            </ul>
                        </li>
                        <li><strong>Сортировка</strong>:
                            <ul>
                                <li>Каналы сортируются по приоритету (от большего к меньшему)</li>
                                <li>Каналы с одинаковым приоритетом сортируются по дате создания</li>
                            </ul>
                        </li>
                    </ul>

                    <h4>Примеры использования:</h4>
                    <ul>
                        <li><strong>Добавить обязательный канал:</strong>
                            <ul>
                                <li>Username: <code>wow_channel</code></li>
                                <li>Название: <code>WOW Официальный канал</code></li>
                                <li>Приоритет: <code>10</code></li>
                                <li>Активен: <code>Да</code></li>
                            </ul>
                        </li>
                        <li><strong>Временно отключить канал:</strong>
                            <ul>
                                <li>Найдите канал в списке</li>
                                <li>Переключите "Активен" в положение "Нет"</li>
                                <li>Пользователи больше не будут обязаны подписываться на этот канал</li>
                            </ul>
                        </li>
                    </ul>

                    <h4>API:</h4>
                    <ul>
                        <li><code>GET /api/v1/wow/channels</code> - Получить список всех каналов</li>
                        <li><code>POST /api/v1/wow/channels</code> - Создать новый канал</li>
                        <li><code>PUT /api/v1/wow/channels/{id}</code> - Обновить канал</li>
                        <li><code>DELETE /api/v1/wow/channels/{id}</code> - Удалить канал</li>
                    </ul>

                    <h3>Раздел: WOW Рулетка → Рулетка</h3>
                    <p><strong>Путь:</strong> <code>/admin/wow/wheel</code></p>
                    <p><strong>Назначение:</strong> Настройка секторов рулетки, вероятностей выпадения и глобальных настроек.</p>
                    
                    <h4>Настройка секторов:</h4>
                    <ul>
                        <li><strong>Всего секторов:</strong> 12 (фиксированное количество)</li>
                        <li><strong>Номер сектора:</strong> от 1 до 12 (уникальный)</li>
                        <li><strong>Тип приза:</strong>
                            <ul>
                                <li><code>empty</code> - Пустой сектор (без приза)</li>
                                <li><code>money</code> - Денежный приз (300₽, 500₽, 1000₽ и т.д.)</li>
                                <li><code>ticket</code> - Билет(ы) для прокрута</li>
                                <li><code>secret_box</code> - Секретный бокс</li>
                                <li><code>sponsor_gift</code> - Подарок от спонсора</li>
                            </ul>
                        </li>
                        <li><strong>Значение приза:</strong>
                            <ul>
                                <li>Для денежных призов: сумма в рублях (300, 500, 1000 и т.д.)</li>
                                <li>Для билетов: количество билетов (обычно 1-10)</li>
                                <li>Для пустых секторов: 0</li>
                            </ul>
                        </li>
                        <li><strong>Иконка сектора:</strong>
                            <ul>
                                <li>URL изображения для отображения на рулетке</li>
                                <li>Можно загрузить через медиа-библиотеку</li>
                                <li>Рекомендуемый размер: 200x200px</li>
                            </ul>
                        </li>
                        <li><strong>Вероятность выпадения:</strong>
                            <ul>
                                <li>Процент от 0 до 100</li>
                                <li>Сумма всех вероятностей должна быть равна 100%</li>
                                <li>Система показывает текущую сумму вероятностей</li>
                                <li>Если сумма не равна 100%, сохранение всех секторов заблокировано</li>
                            </ul>
                        </li>
                        <li><strong>Активность сектора:</strong>
                            <ul>
                                <li>Неактивные секторы не участвуют в прокруте</li>
                                <li>Полезно для временного отключения секторов</li>
                            </ul>
                        </li>
                    </ul>

                    <h4>Глобальные настройки рулетки:</h4>
                    <ul>
                        <li><strong>Режим "Всегда пусто"</strong> (<code>always_empty_mode</code>):
                            <ul>
                                <li>Если включен, колесо всегда останавливается на пустом секторе</li>
                                <li>Вероятности игнорируются</li>
                                <li>Полезно для тестирования или временного отключения выигрышей</li>
                                <li>Пример использования: включить на время технических работ</li>
                            </ul>
                        </li>
                        <li><strong>Период восстановления билета</strong> (<code>ticket_restore_hours</code>):
                            <ul>
                                <li>Интервал в часах, через который восстанавливается 1 билет</li>
                                <li>Диапазон: от 1 до 24 часов</li>
                                <li>По умолчанию: 3 часа</li>
                                <li>Примеры:
                                    <ul>
                                        <li><code>1</code> - билет восстанавливается каждый час (быстрое восстановление)</li>
                                        <li><code>3</code> - билет восстанавливается каждые 3 часа (стандарт)</li>
                                        <li><code>6</code> - билет восстанавливается каждые 6 часов (медленное восстановление)</li>
                                        <li><code>24</code> - билет восстанавливается раз в сутки (очень медленное)</li>
                                    </ul>
                                </li>
                                <li>Изменение применяется немедленно для всех пользователей</li>
                            </ul>
                        </li>
                        <li><strong>Username администратора</strong> (<code>admin_username</code>):
                            <ul>
                                <li>Telegram username администратора (без символа @)</li>
                                <li>Используется для генерации ссылок на администратора</li>
                                <li>Пример: <code>admin_username</code> → ссылка <code>https://t.me/admin_username</code></li>
                            </ul>
                        </li>
                    </ul>

                    <h4>Примеры конфигурации секторов:</h4>
                    <ul>
                        <li><strong>Стандартная конфигурация (рекомендуется):</strong>
                            <ul>
                                <li>7 секторов "Пусто" по 9% каждый = 63%</li>
                                <li>3 сектора "Деньги" (300₽, 500₽, 1000₽) по 8% каждый = 24%</li>
                                <li>1 сектор "Билет" (1 билет) = 5%</li>
                                <li>1 сектор "Секретный бокс" = 8%</li>
                                <li><strong>Итого: 100%</strong></li>
                            </ul>
                        </li>
                        <li><strong>Высокая вероятность выигрыша:</strong>
                            <ul>
                                <li>5 секторов "Пусто" по 10% каждый = 50%</li>
                                <li>4 сектора "Деньги" (300₽, 500₽, 1000₽, 2000₽) по 10% каждый = 40%</li>
                                <li>2 сектора "Билет" (1 билет, 2 билета) по 5% каждый = 10%</li>
                                <li>1 сектор "Секретный бокс" = 0% (неактивен)</li>
                                <li><strong>Итого: 100%</strong></li>
                            </ul>
                        </li>
                        <li><strong>Низкая вероятность выигрыша:</strong>
                            <ul>
                                <li>9 секторов "Пусто" по 10% каждый = 90%</li>
                                <li>2 сектора "Деньги" (300₽, 500₽) по 4% каждый = 8%</li>
                                <li>1 сектор "Билет" (1 билет) = 2%</li>
                                <li><strong>Итого: 100%</strong></li>
                            </ul>
                        </li>
                    </ul>

                    <h4>Массовое обновление:</h4>
                    <ul>
                        <li>Кнопка "Сохранить все" обновляет все 12 секторов одновременно</li>
                        <li>Перед сохранением проверяется, что сумма вероятностей = 100%</li>
                        <li>Если проверка не пройдена, сохранение блокируется</li>
                    </ul>

                    <h4>API:</h4>
                    <ul>
                        <li><code>GET /api/v1/wow/wheel</code> - Получить все секторы</li>
                        <li><code>POST /api/v1/wow/wheel/bulk-update</code> - Обновить все секторы</li>
                        <li><code>PUT /api/v1/wow/wheel/settings</code> - Обновить настройки рулетки</li>
                    </ul>

                    <h3>Раздел: WOW Рулетка → Типы призов</h3>
                    <p><strong>Путь:</strong> <code>/admin/wow/prize-types</code></p>
                    <p><strong>Назначение:</strong> Управление типами призов и их действиями.</p>
                    
                    <h4>Функционал:</h4>
                    <ul>
                        <li><strong>Создание типа приза</strong>:
                            <ul>
                                <li>Название типа (например, "Денежный приз", "Билет", "Секретный бокс")</li>
                                <li>Действие (<code>action</code>):
                                    <ul>
                                        <li><code>add_ticket</code> - Добавить билет(ы) пользователю</li>
                                        <li><code>none</code> - Без автоматического действия (пользователь должен связаться с админом)</li>
                                    </ul>
                                </li>
                                <li>Значение (<code>value</code>):
                                    <ul>
                                        <li>Для <code>add_ticket</code>: количество билетов для добавления</li>
                                        <li>Для <code>none</code>: не используется</li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <li><strong>Связь с секторами:</strong>
                            <ul>
                                <li>Каждый сектор может быть связан с типом приза</li>
                                <li>При выпадении сектора выполняется действие из типа приза</li>
                                <li>Если тип приза имеет <code>action = 'add_ticket'</code>, билеты начисляются автоматически</li>
                            </ul>
                        </li>
                    </ul>

                    <h3>Раздел: WOW Рулетка → Пользователи WOW</h3>
                    <p><strong>Путь:</strong> <code>/admin/wow/users</code></p>
                    <p><strong>Назначение:</strong> Просмотр и управление пользователями рулетки.</p>
                    
                    <h4>Функционал:</h4>
                    <ul>
                        <li><strong>Список пользователей</strong>:
                            <ul>
                                <li>Отображается Telegram ID, username, аватар</li>
                                <li>Баланс билетов и звёзд</li>
                                <li>Статистика: всего прокрутов, выигрышей, приглашений</li>
                                <li>Дата регистрации</li>
                            </ul>
                        </li>
                        <li><strong>Фильтры:</strong>
                            <ul>
                                <li>Поиск по Telegram ID или username</li>
                                <li>Фильтр по дате регистрации</li>
                                <li>Сортировка по различным полям</li>
                            </ul>
                        </li>
                        <li><strong>Детальная информация о пользователе</strong>:
                            <ul>
                                <li>Полный профиль с Telegram данными</li>
                                <li>История последних прокрутов</li>
                                <li>Реферальная статистика (кто пригласил, кого пригласил)</li>
                                <li>История обменов звёзд на билеты</li>
                            </ul>
                        </li>
                        <li><strong>Пагинация:</strong>
                            <ul>
                                <li>По 20 пользователей на страницу</li>
                                <li>Навигация по страницам</li>
                            </ul>
                        </li>
                    </ul>

                    <h4>API:</h4>
                    <ul>
                        <li><code>GET /api/v1/wow/users</code> - Получить список пользователей (с фильтрами и пагинацией)</li>
                        <li><code>GET /api/v1/wow/users/{id}</code> - Получить детальную информацию о пользователе</li>
                    </ul>

                    <h3>Раздел: WOW Рулетка → Рефералы</h3>
                    <p><strong>Путь:</strong> <code>/admin/wow/referrals</code></p>
                    <p><strong>Назначение:</strong> Управление реферальной системой.</p>
                    
                    <h4>Функционал:</h4>
                    <ul>
                        <li><strong>Список всех реферальных связей</strong>:
                            <ul>
                                <li>Информация о пригласившем (inviter)</li>
                                <li>Информация о приглашенном (invited)</li>
                                <li>Дата приглашения</li>
                            </ul>
                        </li>
                        <li><strong>Статистика:</strong>
                            <ul>
                                <li>Всего рефералов в системе</li>
                                <li>Количество активных приглашающих (с приглашениями в текущем месяце)</li>
                                <li>Топ приглашающий пользователь</li>
                            </ul>
                        </li>
                        <li><strong>Фильтры:</strong>
                            <ul>
                                <li>По дате приглашения</li>
                                <li>Поиск по Telegram ID или username</li>
                            </ul>
                        </li>
                    </ul>

                    <h3>Раздел: WOW Рулетка → Статистика</h3>
                    <p><strong>Путь:</strong> <code>/admin/wow/statistics</code></p>
                    <p><strong>Назначение:</strong> Аналитика и метрики системы.</p>
                    
                    <h4>Отображаемые метрики:</h4>
                    <ul>
                        <li><strong>Всего звёзд получено</strong>:
                            <ul>
                                <li>Сумма всех обменов Telegram Stars на билеты</li>
                                <li>Показывает общую выручку от обменов</li>
                            </ul>
                        </li>
                        <li><strong>Всего прокрутов выполнено</strong>:
                            <ul>
                                <li>Общее количество всех прокрутов рулетки</li>
                                <li>Показывает активность пользователей</li>
                            </ul>
                        </li>
                        <li><strong>Распределение призов по типам</strong>:
                            <ul>
                                <li>Визуализация (график/диаграмма)</li>
                                <li>Показывает, сколько раз выпал каждый тип приза</li>
                                <li>Помогает анализировать эффективность настроек вероятностей</li>
                            </ul>
                        </li>
                        <li><strong>Активные пользователи</strong>:
                            <ul>
                                <li>Количество пользователей, которые выполнили хотя бы один прокрут</li>
                            </ul>
                        </li>
                        <li><strong>Общее количество пользователей</strong>:
                            <ul>
                                <li>Все зарегистрированные пользователи</li>
                            </ul>
                        </li>
                    </ul>

                    <h3>Раздел: WOW Рулетка → Призы лидерборда</h3>
                    <p><strong>Путь:</strong> <code>/admin/wow/leaderboard</code></p>
                    <p><strong>Назначение:</strong> Настройка призов за места в лидерборде.</p>
                    
                    <h4>Функционал:</h4>
                    <ul>
                        <li><strong>Настройка призов за места</strong>:
                            <ul>
                                <li>Место (rank): 1, 2, 3, 4, 5, ... (до 10)</li>
                                <li>Сумма приза (prize_amount): в рублях</li>
                                <li>Описание приза (prize_description): текстовое описание</li>
                                <li>Активность приза (is_active): включен/выключен</li>
                            </ul>
                        </li>
                        <li><strong>Период лидерборда</strong> (<code>leaderboard_period_months</code>):
                            <ul>
                                <li>Количество месяцев, за которые считается рейтинг</li>
                                <li>По умолчанию: 1 месяц</li>
                                <li>Примеры:
                                    <ul>
                                        <li><code>1</code> - рейтинг за текущий месяц</li>
                                        <li><code>3</code> - рейтинг за последние 3 месяца</li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                        <li><strong>Примеры конфигурации:</strong>
                            <ul>
                                <li><strong>Стандартная конфигурация:</strong>
                                    <ul>
                                        <li>1 место: 1500₽</li>
                                        <li>2 место: 1000₽</li>
                                        <li>3 место: 500₽</li>
                                        <li>4-10 места: 300₽ каждый</li>
                                    </ul>
                                </li>
                                <li><strong>Премиум конфигурация:</strong>
                                    <ul>
                                        <li>1 место: 5000₽</li>
                                        <li>2 место: 3000₽</li>
                                        <li>3 место: 2000₽</li>
                                        <li>4-5 места: 1000₽ каждый</li>
                                        <li>6-10 места: 500₽ каждый</li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    </ul>

                    <h4>API:</h4>
                    <ul>
                        <li><code>GET /api/v1/wow/leaderboard-prizes</code> - Получить все призы</li>
                        <li><code>PUT /api/v1/wow/leaderboard-prizes/{id}</code> - Обновить приз</li>
                        <li><code>PUT /api/v1/wow/leaderboard-prizes/settings</code> - Обновить период лидерборда</li>
                    </ul>

                    <h3>Раздел: WOW Рулетка → Победители</h3>
                    <p><strong>Путь:</strong> <code>/admin/wow/winners</code></p>
                    <p><strong>Назначение:</strong> Просмотр победителей и управление выплатами призов.</p>
                    
                    <h4>Функционал:</h4>
                    <ul>
                        <li><strong>Список победителей</strong>:
                            <ul>
                                <li>Победители денежных призов из рулетки</li>
                                <li>Победители лидерборда</li>
                                <li>Информация о призе (тип, сумма)</li>
                                <li>Статус выплаты</li>
                            </ul>
                        </li>
                        <li><strong>Управление выплатами</strong>:
                            <ul>
                                <li>Отметить приз как выплаченный</li>
                                <li>Фильтры по статусу выплаты</li>
                            </ul>
                        </li>
                    </ul>

                    <h3>Как изменения в админ-панели влияют на пользовательское приложение</h3>
                    <ul>
                        <li><strong>Изменение вероятностей секторов:</strong>
                            <ul>
                                <li>Применяется немедленно при следующем прокруте</li>
                                <li>Не требует перезагрузки приложения</li>
                                <li>Конфигурация загружается при каждом запросе <code>GET /api/wheel-config</code></li>
                            </ul>
                        </li>
                        <li><strong>Изменение интервала восстановления билетов:</strong>
                            <ul>
                                <li>Применяется для всех пользователей немедленно</li>
                                <li>Влияет на расчет времени до следующего билета</li>
                                <li>Учитывается при автоматическом восстановлении через cron</li>
                            </ul>
                        </li>
                        <li><strong>Включение/выключение режима "Всегда пусто":</strong>
                            <ul>
                                <li>Применяется немедленно</li>
                                <li>Все последующие прокруты будут останавливаться на пустых секторах</li>
                                <li>Полезно для временного отключения выигрышей</li>
                            </ul>
                        </li>
                        <li><strong>Добавление/удаление каналов:</strong>
                            <ul>
                                <li>Проверка подписки обновляется при следующем входе пользователя</li>
                                <li>Новые пользователи должны подписаться на все активные каналы</li>
                            </ul>
                        </li>
                        <li><strong>Изменение призов лидерборда:</strong>
                            <ul>
                                <li>Применяется при следующем расчете лидерборда</li>
                                <li>Влияет на призы за текущий период</li>
                            </ul>
                        </li>
                    </ul>
                `,
                frontend: `
                    <h2>Frontend (React)</h2>
                    <p>React приложение находится в директории <code>/frontend</code>.</p>

                    <h3>Структура</h3>
                    <ul>
                        <li><strong>src/pages/</strong> - Страницы приложения
                            <ul>
                                <li>Index.tsx - Главная страница (рулетка)</li>
                                <li>Friends.tsx - Реферальная система</li>
                                <li>Leaderboard.tsx - Лидерборд</li>
                                <li>HowToPlay.tsx - Инструкция (карусель из 3 слайдов)</li>
                            </ul>
                        </li>
                        <li><strong>src/components/</strong> - Компоненты
                            <ul>
                                <li>WheelComponent.tsx - Компонент рулетки</li>
                                <li>ChannelSubscriptionCheck.tsx - Проверка подписки</li>
                                <li>SpinResultPopup.tsx - Попап с результатом прокрута</li>
                                <li>SecretGiftPopup.tsx - Попап обмена звёзд</li>
                            </ul>
                        </li>
                        <li><strong>src/hooks/</strong> - React хуки
                            <ul>
                                <li>useChannelSubscription.ts - Хук для проверки подписки</li>
                                <li>useTelegramWebApp.ts - Хук для работы с Telegram WebApp API</li>
                            </ul>
                        </li>
                    </ul>

                    <h3>Механика рулетки</h3>
                    <ul>
                        <li>Колесо состоит из 12 секторов (по 30° каждый)</li>
                        <li>Результат определяется на сервере до начала анимации</li>
                        <li>Анимация вращения: 4 секунды, минимум 5 полных оборотов</li>
                        <li>Вероятность выигрыша настраивается в админ-панели</li>
                    </ul>

                    <h3>Система билетов</h3>
                    <ul>
                        <li>Максимум 3 билета у пользователя</li>
                        <li>Один билет = одна попытка прокрута</li>
                        <li>Восстановление билета: каждые 2-4 часа (настраивается)</li>
                        <li>Таймер отображается, если билетов меньше 3</li>
                    </ul>
                `,
                telegram: `
                    <h2>Интеграция с Telegram</h2>

                    <h3>Аутентификация</h3>
                    <ul>
                        <li>Использование <code>initData</code> от Telegram WebApp</li>
                        <li>Валидация подписи через <code>initDataUnsafe.hash</code></li>
                        <li>Автоматическое создание/обновление пользователя при входе</li>
                    </ul>

                    <h3>Проверка подписки на каналы</h3>
                    <ul>
                        <li>Использование Telegram Bot API метода <code>getChatMember</code></li>
                        <li>Бот должен быть администратором каналов</li>
                        <li>Кэширование результатов проверки</li>
                    </ul>

                    <h3>Telegram Stars Exchange</h3>
                    <ul>
                        <li>Интеграция с Telegram Stars Exchange API</li>
                        <li>Обмен 50 звёзд на 20 билетов</li>
                        <li>Обработка webhook'ов для подтверждения транзакций</li>
                    </ul>

                    <h3>Уведомления</h3>
                    <p>Система уведомлений реализована через <code>TelegramNotificationService</code>:</p>
                    <ul>
                        <li><strong>Отправка через Telegram Bot API</strong> - метод <code>sendMessage</code></li>
                        <li><strong>Типы уведомлений:</strong>
                            <ul>
                                <li><code>notifyNewTicket()</code> - Новый билет доступен (автоматически при восстановлении)</li>
                                <li><code>notifyWin()</code> - Выигрыш приза (автоматически после прокрута с выигрышем)</li>
                                <li><code>notifyReminder()</code> - Напоминание о прокрутах (через cron команду)</li>
                            </ul>
                        </li>
                        <li><strong>Автоматические уведомления:</strong>
                            <ul>
                                <li>При восстановлении билета (если билетов было меньше 3)</li>
                                <li>При выигрыше приза (деньги, билет, секретный бокс)</li>
                            </ul>
                        </li>
                        <li><strong>Команды для уведомлений:</strong>
                            <ul>
                                <li><code>php artisan wow:restore-tickets</code> - Восстановление билетов (запускается каждые 3 часа)</li>
                                <li><code>php artisan wow:send-reminders</code> - Отправка напоминаний (запускается ежедневно в 10:00)</li>
                            </ul>
                        </li>
                    </ul>

                    <h3>Настройка бота</h3>
                    <ul>
                        <li>Токен бота в <code>.env</code>: <code>TELEGRAM_BOT_TOKEN</code></li>
                        <li>Бот должен быть администратором всех обязательных каналов</li>
                        <li>Настройка вебхуков для обработки событий</li>
                        <li>Для отправки уведомлений бот должен иметь возможность отправлять сообщения пользователям</li>
                    </ul>

                    <h3>Расписание команд (Cron)</h3>
                    <p>Добавьте в crontab на сервере:</p>
                    <pre><code>* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1</code></pre>
                    <p>Команды автоматически выполняются по расписанию:</p>
                    <ul>
                        <li><code>wow:restore-tickets</code> - каждые 3 часа</li>
                        <li><code>wow:send-reminders</code> - ежедневно в 10:00</li>
                    </ul>
                `,
                notifications: `
                    <h2>Система уведомлений</h2>
                    <p>Система автоматических уведомлений через Telegram Bot API.</p>

                    <h3>TelegramNotificationService</h3>
                    <p>Сервис <code>App\Services\TelegramNotificationService</code> предоставляет методы для отправки уведомлений:</p>
                    <ul>
                        <li><strong>sendNotification(int $telegramId, string $message, array $options): bool</strong>
                            <ul>
                                <li>Базовый метод для отправки сообщения пользователю</li>
                                <li>Поддерживает HTML форматирование</li>
                                <li>Возвращает true при успешной отправке</li>
                            </ul>
                        </li>
                        <li><strong>notifyNewTicket(User $user): bool</strong>
                            <ul>
                                <li>Уведомление о новом билете</li>
                                <li>Вызывается автоматически при восстановлении билета</li>
                            </ul>
                        </li>
                        <li><strong>notifyWin(User $user, int $prizeValue, string $prizeType): bool</strong>
                            <ul>
                                <li>Уведомление о выигрыше</li>
                                <li>Поддерживает типы: money, ticket, secret_box</li>
                                <li>Вызывается автоматически после прокрута с выигрышем</li>
                            </ul>
                        </li>
                        <li><strong>notifyReminder(User $user): bool</strong>
                            <ul>
                                <li>Напоминание о доступных билетах</li>
                                <li>Отправляется только если у пользователя есть билеты</li>
                                <li>Используется в команде <code>wow:send-reminders</code></li>
                            </ul>
                        </li>
                    </ul>

                    <h3>Автоматические уведомления</h3>
                    <ul>
                        <li><strong>При восстановлении билета:</strong>
                            <ul>
                                <li>Проверяется при каждом запросе <code>GET /api/user/tickets</code></li>
                                <li>Если прошло 2-4 часа с последнего прокрута и билетов меньше 3</li>
                                <li>Автоматически отправляется уведомление <code>notifyNewTicket</code></li>
                            </ul>
                        </li>
                        <li><strong>При выигрыше приза:</strong>
                            <ul>
                                <li>После успешного прокрута с выигрышем (не "empty")</li>
                                <li>Автоматически отправляется уведомление <code>notifyWin</code></li>
                                <li>Содержит информацию о типе и размере приза</li>
                            </ul>
                        </li>
                    </ul>

                    <h3>Обработка ошибок</h3>
                    <ul>
                        <li>Все ошибки логируются в Laravel Log</li>
                        <li>Если пользователь заблокировал бота, ошибка обрабатывается корректно</li>
                        <li>Неудачная отправка не блокирует работу приложения</li>
                    </ul>
                `,
                commands: `
                    <h2>Консольные команды</h2>
                    <p>Система включает несколько Artisan команд для автоматизации задач.</p>

                    <h3>wow:restore-tickets</h3>
                    <p><strong>Описание:</strong> Восстановление билетов пользователям (каждые 2-4 часа)</p>
                    <p><strong>Использование:</strong> <code>php artisan wow:restore-tickets</code></p>
                    <p><strong>Логика:</strong></p>
                    <ul>
                        <li>Находит всех пользователей с билетами меньше 3</li>
                        <li>Проверяет, прошло ли достаточно времени с последнего прокрута</li>
                        <li>Восстанавливает 1 билет (до максимума 3)</li>
                        <li>Отправляет уведомление <code>notifyNewTicket</code> при восстановлении</li>
                    </ul>
                    <p><strong>Расписание:</strong> Каждые 3 часа (настраивается в <code>routes/console.php</code>)</p>

                    <h3>wow:send-reminders</h3>
                    <p><strong>Описание:</strong> Отправка напоминаний пользователям о доступных билетах</p>
                    <p><strong>Использование:</strong> <code>php artisan wow:send-reminders</code></p>
                    <p><strong>Логика:</strong></p>
                    <ul>
                        <li>Находит пользователей с билетами, которые не крутили более 24 часов</li>
                        <li>Отправляет напоминание через <code>notifyReminder</code></li>
                        <li>Пропускает пользователей без билетов</li>
                    </ul>
                    <p><strong>Расписание:</strong> Ежедневно в 10:00 (настраивается в <code>routes/console.php</code>)</p>

                    <h3>Настройка расписания</h3>
                    <p>Расписание команд настраивается в файле <code>routes/console.php</code>:</p>
                    <pre><code>Schedule::command('wow:restore-tickets')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('wow:send-reminders')
    ->dailyAt('10:00')
    ->withoutOverlapping()
    ->runInBackground();</code></pre>

                    <h3>Настройка Cron</h3>
                    <p>Для автоматического выполнения команд добавьте в crontab:</p>
                    <pre><code>* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1</code></pre>
                    <p>Это запустит планировщик каждую минуту, который проверит, какие команды нужно выполнить.</p>

                    <h3>Конфигурация</h3>
                    <p>Интервал восстановления билетов настраивается в админ панели:</p>
                    <ul>
                        <li>Перейдите в раздел <strong>WOW Рулетка</strong> → <strong>Рулетка</strong></li>
                        <li>В блоке "Настройки рулетки" найдите поле "Период восстановления билета"</li>
                        <li>Установите значение от 1 до 24 часов</li>
                        <li>Настройка сохраняется автоматически при изменении</li>
                    </ul>
                    <p><strong>По умолчанию:</strong> 3 часа.</p>
                    <p><strong>Примечание:</strong> Настройка хранится в таблице <code>wheel_settings</code> в поле <code>ticket_restore_hours</code>.</p>
                `,
            },
        };
    },
    computed: {
        currentContent() {
            return this.documentation[this.activeSection] || '<p>Раздел в разработке</p>';
        },
    },
};
</script>

<style scoped>
.documentation-page {
    min-height: 100vh;
}

.prose {
    color: inherit;
}

.prose h2 {
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-size: 1.5rem;
    font-weight: 700;
}

.prose h3 {
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    font-size: 1.25rem;
    font-weight: 600;
}

.prose ul {
    margin: 1rem 0;
    padding-left: 1.5rem;
}

.prose li {
    margin: 0.5rem 0;
}

.prose code {
    background-color: rgba(0, 0, 0, 0.05);
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-family: 'Courier New', monospace;
    font-size: 0.875em;
}

.dark .prose code {
    background-color: rgba(255, 255, 255, 0.1);
}
</style>

