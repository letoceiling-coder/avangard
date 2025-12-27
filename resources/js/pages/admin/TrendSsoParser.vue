<template>
    <div class="container mx-auto p-6">
        <div class="mb-6">
            <h1 class="text-3xl font-bold mb-2">Авторизация Trend SSO</h1>
            <p class="text-muted-foreground">Авторизация через Trend SSO API</p>
        </div>

        <!-- Форма авторизации -->
        <div class="bg-card rounded-lg border p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Параметры авторизации</h2>
            <form @submit.prevent="handleAuthenticate" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Телефон</label>
                        <input
                            v-model="form.phone"
                            type="tel"
                            required
                            autocomplete="tel"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            placeholder="+7 999 637 11 82"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Пароль</label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            placeholder="Пароль"
                        />
                    </div>
                </div>
                <button
                    type="submit"
                    :disabled="loading"
                    class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 disabled:opacity-50"
                >
                    <span v-if="!loading">Авторизоваться</span>
                    <span v-else>Выполняется...</span>
                </button>
            </form>
        </div>

        <!-- Кнопка получения контента -->
        <div v-if="authData && authData.authenticated" class="bg-card rounded-lg border p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Получение контента</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2">URL страницы</label>
                    <input
                        v-model="contentUrl"
                        type="url"
                        class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                        placeholder="https://spb.trendagent.ru/objects/list"
                    />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Тип объекта</label>
                        <select
                            v-model="pagination.object_type"
                            @change="handleGetContent(true)"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                            <option value="apartments">Квартиры</option>
                            <option value="parking">Паркинг</option>
                            <option value="houses">Дома с участками</option>
                            <option value="plots">Участки</option>
                            <option value="commercial">Коммерция</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Количество объектов</label>
                        <select
                            v-model="pagination.count"
                            @change="handleGetContent(true)"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Сортировка</label>
                        <select
                            v-model="pagination.sort"
                            @change="handleGetContent(true)"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                            <option value="price">По цене</option>
                            <option value="name">По названию</option>
                            <option value="deadline">По сроку сдачи</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Порядок</label>
                        <select
                            v-model="pagination.sort_order"
                            @change="handleGetContent(true)"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                            <option value="asc">По возрастанию</option>
                            <option value="desc">По убыванию</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="handleGetContent"
                        :disabled="loadingContent"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 disabled:opacity-50"
                    >
                        <span v-if="!loadingContent">Получить контент</span>
                        <span v-else>Загрузка...</span>
                    </button>
                    <button
                        v-if="contentData && contentData.pagination && contentData.pagination.offset > 0"
                        @click="loadPreviousPage"
                        :disabled="loadingContent"
                        class="px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/90 disabled:opacity-50"
                    >
                        ← Предыдущие
                    </button>
                    <button
                        v-if="contentData && contentData.pagination && contentData.pagination.has_more"
                        @click="loadNextPage"
                        :disabled="loadingContent"
                        class="px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/90 disabled:opacity-50"
                    >
                        Следующие →
                    </button>
                </div>
            </div>
        </div>

        <!-- Статус авторизации -->
        <div v-if="status" class="bg-card rounded-lg border p-6">
            <h2 class="text-xl font-semibold mb-4">Статус авторизации</h2>
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div
                        :class="[
                            'w-4 h-4 rounded-full',
                            status.type === 'success' ? 'bg-green-500' : 
                            status.type === 'error' ? 'bg-red-500' : 'bg-yellow-500'
                        ]"
                    ></div>
                    <div>
                        <div class="font-medium">{{ status.title }}</div>
                        <div class="text-sm text-muted-foreground">{{ status.message }}</div>
                    </div>
                </div>

                <!-- Детали авторизации -->
                <div v-if="authData" class="mt-4 pt-4 border-t">
                    <h3 class="font-semibold mb-3">Детали авторизации</h3>
                    <div class="space-y-2 text-sm">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-muted-foreground">Статус</div>
                                <div class="font-medium">
                                    <span v-if="authData.authenticated" class="text-green-600">Авторизован</span>
                                    <span v-else class="text-red-600">Не авторизован</span>
                                </div>
                            </div>
                            <div v-if="authData.timestamp">
                                <div class="text-muted-foreground">Время авторизации</div>
                                <div class="font-medium">{{ formatDate(authData.timestamp) }}</div>
                            </div>
                        </div>
                        
                        <div v-if="authData.user">
                            <div class="text-muted-foreground mb-1">Данные пользователя</div>
                            <div class="space-y-1 bg-muted p-3 rounded">
                                <div v-if="authData.user.name" class="text-sm">
                                    <span class="text-muted-foreground">Имя:</span>
                                    <span class="font-medium ml-2">{{ authData.user.name }}</span>
                                </div>
                                <div v-if="authData.user.email" class="text-sm">
                                    <span class="text-muted-foreground">Email:</span>
                                    <span class="font-medium ml-2">{{ authData.user.email }}</span>
                                </div>
                                <div v-if="authData.user.phone" class="text-sm">
                                    <span class="text-muted-foreground">Телефон:</span>
                                    <span class="font-medium ml-2">{{ authData.user.phone }}</span>
                                </div>
                                <div v-if="authData.user.id" class="text-sm">
                                    <span class="text-muted-foreground">ID:</span>
                                    <span class="font-mono text-xs ml-2">{{ authData.user.id }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="authData.tokens && Object.keys(authData.tokens).length > 0">
                            <div class="text-muted-foreground mb-1">Токены</div>
                            <div class="space-y-1">
                                <div v-if="authData.tokens.access_token" class="font-mono text-xs bg-muted p-2 rounded break-all">
                                    <div class="text-muted-foreground text-xs mb-1">Access Token:</div>
                                    <div>{{ truncateToken(authData.tokens.access_token) }}</div>
                                </div>
                                <div v-if="authData.tokens.auth_token" class="font-mono text-xs bg-muted p-2 rounded break-all">
                                    <div class="text-muted-foreground text-xs mb-1">Auth Token:</div>
                                    <div>{{ truncateToken(authData.tokens.auth_token) }}</div>
                                </div>
                                <div v-if="authData.tokens.refresh_token" class="font-mono text-xs bg-muted p-2 rounded break-all">
                                    <div class="text-muted-foreground text-xs mb-1">Refresh Token:</div>
                                    <div>{{ truncateToken(authData.tokens.refresh_token) }}</div>
                                </div>
                            </div>
                        </div>

                        <div v-if="authData.session_id">
                            <div class="text-muted-foreground">Session ID</div>
                            <div class="font-mono text-xs bg-muted p-2 rounded break-all">{{ authData.session_id }}</div>
                        </div>

                        <div v-if="authData.current_url">
                            <div class="text-muted-foreground">Текущий URL</div>
                            <div class="text-xs break-all">{{ authData.current_url }}</div>
                        </div>

                        <div v-if="authData.cookies && Object.keys(authData.cookies).length > 0">
                            <div class="text-muted-foreground mb-1">Cookies ({{ Object.keys(authData.cookies).length }})</div>
                            <div class="space-y-1 max-h-40 overflow-y-auto">
                                <div 
                                    v-for="(cookie, name) in authData.cookies" 
                                    :key="name"
                                    class="text-xs bg-muted p-2 rounded"
                                >
                                    <div class="font-medium">{{ name }}</div>
                                    <div class="text-muted-foreground truncate">{{ cookie.value }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Результаты получения контента -->
        <div v-if="contentData" class="bg-card rounded-lg border p-6">
            <h2 class="text-xl font-semibold mb-4">Контент страницы</h2>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-muted-foreground">URL</div>
                        <div class="font-medium break-all">{{ contentData.url }}</div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">Тип объекта</div>
                        <div class="font-medium">
                            {{ getObjectTypeLabel(contentData.object_type) }}
                            <span v-if="contentData.room_filter" class="text-xs text-muted-foreground">
                                (room: {{ contentData.room_filter.join(', ') }})
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">API Endpoint</div>
                        <div class="font-medium text-xs break-all">{{ contentData.api_endpoint }}</div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">Источник</div>
                        <div class="font-medium">{{ contentData.source }}</div>
                    </div>
                </div>

                <!-- Данные из API -->
                <div v-if="contentData.data" class="mt-4 pt-4 border-t">
                    <h3 class="font-semibold mb-3">Данные объектов</h3>
                    <div class="space-y-2 text-sm">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-muted-foreground">Всего объектов (blocks)</div>
                                <div class="font-medium text-lg">{{ contentData.data.blocks_count }}</div>
                            </div>
                            <div>
                                <div class="text-muted-foreground">Превью объектов (prelaunches)</div>
                                <div class="font-medium text-lg">{{ contentData.data.prelaunches_count }}</div>
                            </div>
                            <div v-if="contentData.object_type !== 'parking'">
                                <div class="text-muted-foreground">Всего квартир</div>
                                <div class="font-medium text-lg">{{ formatNumber(contentData.data.apartments_count) }}</div>
                            </div>
                            <div v-if="contentData.object_type !== 'parking'">
                                <div class="text-muted-foreground">Забронировано</div>
                                <div class="font-medium text-lg">{{ formatNumber(contentData.data.booked_apartments_count) }}</div>
                            </div>
                            <div v-if="contentData.object_type !== 'parking'">
                                <div class="text-muted-foreground">На просмотре</div>
                                <div class="font-medium text-lg">{{ formatNumber(contentData.data.view_apartments_count) }}</div>
                            </div>
                            <div v-if="contentData.object_type === 'parking'">
                                <div class="text-muted-foreground">Всего мест</div>
                                <div class="font-medium text-lg">{{ formatNumber(contentData.data.places_count) }}</div>
                            </div>
                            <div v-if="contentData.object_type === 'parking'">
                                <div class="text-muted-foreground">Забронировано мест</div>
                                <div class="font-medium text-lg">{{ formatNumber(contentData.data.booked_places_count) }}</div>
                            </div>
                            <div v-if="contentData.object_type === 'plots'">
                                <div class="text-muted-foreground">Всего участков</div>
                                <div class="font-medium text-lg">{{ formatNumber(contentData.data.plots_count) }}</div>
                            </div>
                            <div v-if="contentData.object_type === 'plots'">
                                <div class="text-muted-foreground">Всего поселков</div>
                                <div class="font-medium text-lg">{{ formatNumber(contentData.data.total_count) }}</div>
                            </div>
                            <div v-if="contentData.object_type === 'commercial'">
                                <div class="text-muted-foreground">Всего помещений</div>
                                <div class="font-medium text-lg">{{ formatNumber(contentData.data.premises_count) }}</div>
                            </div>
                            <div v-if="contentData.object_type === 'commercial'">
                                <div class="text-muted-foreground">Забронировано помещений</div>
                                <div class="font-medium text-lg">{{ formatNumber(contentData.data.booked_premises_count) }}</div>
                            </div>
                            <div>
                                <div class="text-muted-foreground">В списке</div>
                                <div class="font-medium text-lg">{{ contentData.data.objects_count }}</div>
                            </div>
                        </div>
                        
                        <!-- Информация о пагинации -->
                        <div v-if="contentData.pagination" class="mt-4 pt-4 border-t">
                            <div class="flex items-center justify-between text-sm">
                                <div class="text-muted-foreground">
                                    Показано: {{ contentData.pagination.offset + 1 }} - {{ contentData.pagination.offset + contentData.data.objects_count }} из {{ contentData.data.blocks_count }}
                                </div>
                                <div class="text-muted-foreground">
                                    Страница: {{ contentData.pagination.page }}
                                </div>
                            </div>
                        </div>
                        
                        <!-- Информация о структуре изображений -->
                        <div v-if="contentData.data.objects && contentData.data.objects.length > 0 && contentData.data.objects[0].image" class="mt-4 pt-4 border-t">
                            <h4 class="font-semibold mb-2">Структура изображений</h4>
                            <div class="bg-muted p-3 rounded text-xs">
                                <div class="font-mono space-y-1">
                                    <div><span class="text-muted-foreground">Формат URL:</span> https://selcdn.trendagent.ru/images/{path}/m_{file_name}</div>
                                    <div v-if="contentData.data.objects[0].image.path" class="mt-2">
                                        <div><span class="text-muted-foreground">Path:</span> {{ contentData.data.objects[0].image.path }}</div>
                                        <div><span class="text-muted-foreground">File name:</span> {{ contentData.data.objects[0].image.file_name }}</div>
                                        <div v-if="contentData.data.objects[0].image.url" class="mt-1 break-all">
                                            <span class="text-muted-foreground">URL:</span> 
                                            <a :href="contentData.data.objects[0].image.url" target="_blank" class="text-primary hover:underline">
                                                {{ contentData.data.objects[0].image.url }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Список объектов -->
                        <div v-if="contentData.data.objects && contentData.data.objects.length > 0" class="mt-4">
                            <h4 class="font-semibold mb-2">Объекты ({{ contentData.data.objects.length }})</h4>
                            <div class="space-y-4 max-h-96 overflow-y-auto">
                                <div
                                    v-for="(object, index) in contentData.data.objects"
                                    :key="index"
                                    class="bg-muted p-4 rounded-lg border"
                                >
                                    <div class="flex gap-4">
                                        <!-- Изображение -->
                                        <div v-if="getObjectImage(object)" class="flex-shrink-0">
                                            <img
                                                :src="getObjectImage(object)"
                                                :alt="object.name || `Объект #${index + 1}`"
                                                class="w-32 h-32 object-cover rounded"
                                                @error="handleImageError"
                                            />
                                        </div>
                                        
                                        <!-- Информация об объекте -->
                                        <div class="flex-1 space-y-2 text-sm">
                                            <div class="font-medium text-base">{{ object.name || `Объект #${index + 1}` }}</div>
                                            
                                            <div v-if="object.builder" class="text-muted-foreground">
                                                <span class="font-medium">Застройщик:</span> {{ object.builder.name }}
                                            </div>
                                            
                                            <div v-if="object.address && object.address.length > 0" class="text-muted-foreground">
                                                <span class="font-medium">Адрес:</span> {{ object.address[0] }}
                                            </div>
                                            
                                            <div v-if="object.region" class="text-muted-foreground">
                                                <span class="font-medium">Район:</span> {{ object.region.name }}
                                            </div>
                                            
                                            <div v-if="getObjectPrice(object)" class="text-muted-foreground">
                                                <span class="font-medium">Цена:</span> {{ getObjectPrice(object) }}
                                            </div>
                                            
                                            <div v-if="object.deadline" class="text-muted-foreground">
                                                <span class="font-medium">Срок сдачи:</span> {{ object.deadline }}
                                            </div>
                                            
                                            <div v-if="object.apart_count" class="text-muted-foreground">
                                                <span class="font-medium">Квартир:</span> {{ object.apart_count }} 
                                                <span v-if="object.view_apart_count">(на просмотре: {{ object.view_apart_count }})</span>
                                            </div>
                                            
                                            <div v-if="object.finishing" class="text-muted-foreground">
                                                <span class="font-medium">Отделка:</span> {{ object.finishing }}
                                            </div>
                                            
                                            <div v-if="object.plots_count" class="text-muted-foreground">
                                                <span class="font-medium">Количество участков:</span> {{ object.plots_count }}
                                                <span v-if="object.view_plots_count"> (на просмотре: {{ object.view_plots_count }})</span>
                                            </div>
                                            
                                            <div v-if="object.premises_count" class="text-muted-foreground">
                                                <span class="font-medium">Количество помещений:</span> {{ object.premises_count }}
                                            </div>
                                            
                                            <div v-if="object.district" class="text-muted-foreground">
                                                <span class="font-medium">Район:</span> {{ object.district.name }}
                                            </div>
                                            
                                            <div v-if="object.distance" class="text-muted-foreground">
                                                <div v-if="object.distance.center">
                                                    <span class="font-medium">{{ object.distance.center.label }}</span> {{ object.distance.center.value }}
                                                </div>
                                                <div v-if="object.distance.railway">
                                                    <span class="font-medium">{{ object.distance.railway.label }}</span> {{ object.distance.railway.value }}
                                                </div>
                                                <div v-if="object.distance.highway">
                                                    <span class="font-medium">{{ object.distance.highway.label }}</span> {{ object.distance.highway.value }}
                                                </div>
                                            </div>
                                            
                                            <div v-if="getObjectReward(object)" class="mt-2">
                                                <span class="bg-primary/20 text-primary px-2 py-1 rounded text-xs">
                                                    Комиссия: {{ getObjectReward(object) }}
                                                </span>
                                            </div>
                                            
                                            <!-- Ссылка на изображение -->
                                            <div v-if="object.image && object.image.url" class="mt-2">
                                                <a 
                                                    :href="object.image.url" 
                                                    target="_blank" 
                                                    class="text-xs text-primary hover:underline"
                                                >
                                                    Открыть изображение
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'TrendSsoParser',
    data() {
        return {
            form: {
                phone: '+79045393434',
                password: 'nwBvh4q',
            },
            loading: false,
            loadingContent: false,
            status: null,
            authData: null,
            contentUrl: 'https://spb.trendagent.ru/objects/list',
            contentData: null,
            pagination: {
                count: 20,
                offset: 0,
                page: 1,
                sort: 'price',
                sort_order: 'asc',
                object_type: 'apartments', // apartments, parking, houses, plots, commercial
            },
        };
    },
    methods: {
        async handleAuthenticate() {
            this.loading = true;
            this.status = {
                type: 'info',
                title: 'Выполняется...',
                message: 'Авторизация через Trend SSO API...',
            };
            this.authData = null;

            try {
                const response = await axios.post('/api/trendsso/authenticate', {
                    phone: this.form.phone,
                    password: this.form.password,
                });

                if (response.data.success) {
                    this.status = {
                        type: 'success',
                        title: 'Авторизация успешна',
                        message: 'Вы успешно авторизованы через Trend SSO API',
                    };
                    this.authData = response.data.data || {};
                } else {
                    throw new Error(response.data.message || 'Ошибка авторизации');
                }

            } catch (error) {
                let errorMessage = 'Произошла ошибка';
                
                if (error.response) {
                    errorMessage = error.response.data?.message || 
                                 error.response.data?.error || 
                                 `Ошибка сервера: ${error.response.status}`;
                } else if (error.request) {
                    errorMessage = 'Не удалось получить ответ от сервера. Проверьте подключение к интернету.';
                } else {
                    errorMessage = error.message || 'Ошибка при выполнении запроса';
                }
                
                this.status = {
                    type: 'error',
                    title: 'Ошибка авторизации',
                    message: errorMessage,
                };
                console.error('Ошибка:', error);
            } finally {
                this.loading = false;
            }
        },
        truncateToken(token) {
            if (!token) return '';
            if (token.length <= 50) return token;
            return token.substring(0, 25) + '...' + token.substring(token.length - 25);
        },
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('ru-RU');
        },
        async handleGetContent(resetPagination = false) {
            if (!this.authData || !this.authData.authenticated) {
                this.status = {
                    type: 'error',
                    title: 'Ошибка',
                    message: 'Сначала выполните авторизацию',
                };
                return;
            }

            // Сбрасываем пагинацию, если нужно
            if (resetPagination) {
                this.pagination.offset = 0;
                this.pagination.page = 1;
            }

            this.loadingContent = true;
            this.contentData = null;

            try {
                const response = await axios.post('/api/trendsso/objects-list', {
                    phone: this.form.phone,
                    password: this.form.password,
                    url: this.contentUrl,
                    parse: true,
                    count: this.pagination.count,
                    offset: this.pagination.offset,
                    page: this.pagination.page,
                    sort: this.pagination.sort,
                    sort_order: this.pagination.sort_order,
                    object_type: this.pagination.object_type,
                });

                if (response.data.success) {
                    this.contentData = response.data;
                    // Обновляем пагинацию из ответа
                    if (response.data.pagination) {
                        this.pagination.offset = response.data.pagination.offset;
                        this.pagination.page = response.data.pagination.page;
                    }
                } else {
                    throw new Error(response.data.message || 'Ошибка получения контента');
                }

            } catch (error) {
                let errorMessage = 'Произошла ошибка';
                
                if (error.response) {
                    errorMessage = error.response.data?.message || 
                                 error.response.data?.error || 
                                 `Ошибка сервера: ${error.response.status}`;
                } else if (error.request) {
                    errorMessage = 'Не удалось получить ответ от сервера. Проверьте подключение к интернету.';
                } else {
                    errorMessage = error.message || 'Ошибка при выполнении запроса';
                }
                
                this.status = {
                    type: 'error',
                    title: 'Ошибка получения контента',
                    message: errorMessage,
                };
                console.error('Ошибка:', error);
            } finally {
                this.loadingContent = false;
            }
        },
        async loadNextPage() {
            if (!this.contentData || !this.contentData.pagination) return;
            
            this.pagination.offset += this.pagination.count;
            this.pagination.page += 1;
            await this.handleGetContent();
        },
        async loadPreviousPage() {
            if (!this.contentData || !this.contentData.pagination || this.pagination.offset === 0) return;
            
            this.pagination.offset = Math.max(0, this.pagination.offset - this.pagination.count);
            this.pagination.page = Math.max(1, this.pagination.page - 1);
            await this.handleGetContent();
        },
        formatBytes(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },
        formatNumber(num) {
            if (!num) return '0';
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        },
        formatPrice(price) {
            if (!price) return '0';
            return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        },
        getObjectTypeLabel(type) {
            const labels = {
                'apartments': 'Квартиры',
                'parking': 'Паркинг',
                'houses': 'Дома с участками',
                'plots': 'Участки',
                'commercial': 'Коммерция',
            };
            return labels[type] || type;
        },
        getObjectImage(object) {
            // Для паркинга images - массив, для остальных image - объект
            if (object.images && Array.isArray(object.images) && object.images.length > 0) {
                return object.images[0].thumbnail || object.images[0].full;
            }
            if (object.image && object.image.url) {
                return object.image.url;
            }
            return null;
        },
        getObjectPrice(object) {
            // Для участков min_prices имеет другую структуру (label, value, unit)
            // Для коммерции min_prices имеет структуру с purpose и price
            if (object.min_prices && Array.isArray(object.min_prices) && object.min_prices.length > 0) {
                // Проверяем, это участки (есть label и value как строка) или паркинг/коммерция (есть price)
                if (object.min_prices[0].value && typeof object.min_prices[0].value === 'string' && !object.min_prices[0].price) {
                    // Участки: форматируем как список
                    return object.min_prices.map(p => {
                        return `${p.label}: ${p.value} ${p.unit || '₽'}`;
                    }).join(', ');
                } else {
                    // Паркинг или коммерция: используем price
                    const prices = object.min_prices.map(p => p.price).filter(p => p !== null && p !== undefined);
                    if (prices.length > 0) {
                        const minPrice = Math.min(...prices);
                        const maxPrice = Math.max(...prices);
                        if (minPrice === maxPrice) {
                            return `${this.formatPrice(minPrice)} ₽`;
                        }
                        // Для коммерции показываем с назначением
                        if (object.min_prices[0].purpose) {
                            return object.min_prices.map(p => {
                                const purposeLabel = p.purpose?.label || p.value || '';
                                return `${purposeLabel}: ${this.formatPrice(p.price)} ₽`;
                            }).join(', ');
                        }
                        return `${this.formatPrice(minPrice)} - ${this.formatPrice(maxPrice)} ₽`;
                    }
                }
            }
            if (object.min_price && object.max_price) {
                return `${this.formatPrice(object.min_price)} - ${this.formatPrice(object.max_price)} ₽`;
            }
            if (object.min_price) {
                return `от ${this.formatPrice(object.min_price)} ₽`;
            }
            return null;
        },
        formatReward(rewards) {
            if (!Array.isArray(rewards) || rewards.length === 0) return '';
            
            // Если элементы массива - объекты с полями label/value или просто строки
            return rewards.map(reward => {
                if (typeof reward === 'string') {
                    return reward;
                }
                if (reward.label && reward.value) {
                    return `${reward.label}: ${reward.value}`;
                }
                if (reward.value) {
                    return reward.value;
                }
                return JSON.stringify(reward);
            }).join(', ');
        },
        getObjectReward(object) {
            // Для паркинга reward - строка, для остальных reward - массив
            if (typeof object.reward === 'string') {
                return object.reward;
            }
            if (Array.isArray(object.reward) && object.reward.length > 0) {
                return this.formatReward(object.reward);
            }
            return null;
        },
        handleImageError(event) {
            // Заменяем изображение на placeholder при ошибке загрузки
            event.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTI4IiBoZWlnaHQ9IjEyOCIgdmlld0JveD0iMCAwIDEyOCAxMjgiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMjgiIGhlaWdodD0iMTI4IiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik02NCA0MEM1Ny4zNzI2IDQwIDUyIDQ1LjM3MjYgNTIgNTJDNTIgNTguNjI3NCA1Ny4zNzI2IDY0IDY0IDY0QzcwLjYyNzQgNjQgNzYgNTguNjI3NCA3NiA1MkM3NiA0NS4zNzI2IDcwLjYyNzQgNDAgNjQgNDBaIiBmaWxsPSIjOUI5Q0E0Ii8+CjxwYXRoIGQ9Ik00MCA4OEw1MiA3Mkw2NCA4MEw3NiA3Mkw4OCA4OEg0MFoiIGZpbGw9IiM5QjlDQTQiLz4KPC9zdmc+';
        },
    },
};
</script>
