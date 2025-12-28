<template>
    <div class="parser-objects-page space-y-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Объекты парсера</h1>
                <p class="text-muted-foreground mt-1">Просмотр и управление объектами из парсера TrendAgent</p>
            </div>
        </div>

        <!-- Type Selector -->
        <div class="bg-card rounded-lg border border-border p-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="type in objectTypes"
                    :key="type.value"
                    @click="currentType = type.value; fetchObjects()"
                    :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                        currentType === type.value
                            ? 'bg-accent text-accent-foreground'
                            : 'bg-muted text-muted-foreground hover:bg-muted/80'
                    ]"
                >
                    {{ type.label }}
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-card rounded-lg border border-border p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Поиск</label>
                    <input
                        v-model="filters.search"
                        @input="debouncedFetchObjects"
                        type="text"
                        placeholder="Название или адрес..."
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                    />
                </div>
                
                <!-- City Filter -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Город</label>
                    <select
                        v-model="filters.city_id"
                        @change="fetchObjects"
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                    >
                        <option value="">Все города</option>
                        <option v-for="city in cities" :key="city.id" :value="city.id">
                            {{ city.name }}
                        </option>
                    </select>
                </div>
                
                <!-- Data Source Filter -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Источник</label>
                    <select
                        v-model="filters.data_source"
                        @change="fetchObjects"
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                    >
                        <option value="">Все источники</option>
                        <option value="parser">Парсер</option>
                        <option value="manual">Вручную</option>
                        <option value="feed">Feed</option>
                        <option value="import">Импорт</option>
                    </select>
                </div>
                
                <!-- Active Filter -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Статус</label>
                    <select
                        v-model="filters.is_active"
                        @change="fetchObjects"
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                    >
                        <option value="">Все</option>
                        <option value="1">Активные</option>
                        <option value="0">Неактивные</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка объектов...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Objects Table -->
        <div v-if="!loading && objects.length > 0" class="bg-card rounded-lg border border-border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-muted/30 border-b border-border">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Название</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Адрес</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Город</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Источник</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Обновлено</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="obj in objects" :key="obj.id" class="hover:bg-muted/10">
                            <td class="px-6 py-4 text-sm text-foreground">{{ obj.id }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-foreground">{{ obj.name }}</td>
                            <td class="px-6 py-4 text-sm text-foreground">{{ obj.address || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-foreground">{{ obj.city?.name || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                <span :class="[
                                    'px-2 py-1 text-xs rounded-md',
                                    obj.data_source === 'parser' ? 'bg-blue-500/10 text-blue-500' : 'bg-gray-500/10 text-gray-500'
                                ]">
                                    {{ getDataSourceLabel(obj.data_source) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-foreground">
                                <span :class="[
                                    'px-2 py-1 text-xs rounded-md',
                                    obj.is_active ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'
                                ]">
                                    {{ obj.is_active ? 'Активен' : 'Неактивен' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-muted-foreground">
                                {{ obj.updated_at ? new Date(obj.updated_at).toLocaleDateString('ru-RU') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="checkActuality(obj)"
                                        :disabled="checkingActuality === obj.id"
                                        class="px-3 py-1 text-xs bg-yellow-500 hover:bg-yellow-600 text-white rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                        title="Проверить актуальность данных"
                                    >
                                        {{ checkingActuality === obj.id ? 'Проверка...' : 'Проверить актуальность' }}
                                    </button>
                                    <button
                                        @click="viewObject(obj)"
                                        class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                                    >
                                        Просмотр
                                    </button>
                                    <button
                                        @click="editObject(obj)"
                                        class="px-3 py-1 text-xs bg-green-500 hover:bg-green-600 text-white rounded transition-colors"
                                    >
                                        Редактировать
                                    </button>
                                    <button
                                        @click="deleteObject(obj)"
                                        class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition-colors"
                                    >
                                        Удалить
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && objects.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Объекты не найдены</p>
        </div>

        <!-- Pagination -->
        <div v-if="!loading && pagination && pagination.last_page > 1" class="flex items-center justify-between bg-card rounded-lg border border-border p-4">
            <div class="text-sm text-muted-foreground">
                Показано {{ pagination.from }} - {{ pagination.to }} из {{ pagination.total }}
            </div>
            <div class="flex gap-2">
                <button
                    @click="changePage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="px-4 py-2 text-sm border border-border rounded-lg hover:bg-muted/10 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Назад
                </button>
                <button
                    @click="changePage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="px-4 py-2 text-sm border border-border rounded-lg hover:bg-muted/10 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Вперед
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { apiGet, apiPost, apiDelete } from '../../utils/api';

export default {
    name: 'ParserObjects',
    data() {
        return {
            loading: false,
            error: null,
            currentType: 'blocks',
            objects: [],
            cities: [],
            pagination: null,
            filters: {
                search: '',
                city_id: '',
                data_source: '',
                is_active: '',
            },
            objectTypes: [
                { value: 'blocks', label: 'Квартиры', endpoint: '/blocks' },
                { value: 'parkings', label: 'Паркинги', endpoint: '/parkings' },
                { value: 'villages', label: 'Поселки', endpoint: '/villages' },
                { value: 'plots', label: 'Участки', endpoint: '/plots' },
                { value: 'commercial-blocks', label: 'Коммерческие объекты', endpoint: '/commercial-blocks' },
                { value: 'commercial-premises', label: 'Коммерческие помещения', endpoint: '/commercial-premises' },
            ],
            debounceTimer: null,
            checkingActuality: null,
        };
    },
    async mounted() {
        await this.fetchCities();
        await this.fetchObjects();
    },
    methods: {
        async fetchCities() {
            try {
                const response = await apiGet('/regions');
                const data = await response.json();
                if (data.data) {
                    const allCities = [];
                    data.data.forEach(region => {
                        if (region.cities) {
                            allCities.push(...region.cities);
                        }
                    });
                    this.cities = allCities;
                }
            } catch (error) {
                console.error('Error fetching cities:', error);
            }
        },
        async fetchObjects(page = 1) {
            this.loading = true;
            this.error = null;
            
            try {
                const typeConfig = this.objectTypes.find(t => t.value === this.currentType);
                const params = {
                    page,
                    per_page: 15,
                    ...this.filters,
                };
                
                // Удаляем пустые параметры
                Object.keys(params).forEach(key => {
                    if (params[key] === '' || params[key] === null) {
                        delete params[key];
                    }
                });
                
                const response = await apiGet(typeConfig.endpoint, params);
                const data = await response.json();
                
                if (response.ok) {
                    this.objects = data.data || [];
                    this.pagination = {
                        current_page: data.current_page || 1,
                        last_page: data.last_page || 1,
                        from: data.from || 0,
                        to: data.to || 0,
                        total: data.total || 0,
                    };
                } else {
                    this.error = data.message || 'Ошибка при загрузке объектов';
                }
            } catch (error) {
                console.error('Error fetching objects:', error);
                this.error = 'Ошибка при загрузке объектов';
            } finally {
                this.loading = false;
            }
        },
        debouncedFetchObjects() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.fetchObjects();
            }, 500);
        },
        changePage(page) {
            this.fetchObjects(page);
        },
        viewObject(obj) {
            // Здесь можно открыть модальное окно или перейти на страницу детального просмотра
            console.log('View object:', obj);
        },
        editObject(obj) {
            // Здесь можно открыть модальное окно редактирования
            console.log('Edit object:', obj);
        },
        async deleteObject(obj) {
            if (!confirm(`Вы уверены, что хотите удалить объект "${obj.name}"?`)) {
                return;
            }
            
            try {
                const typeConfig = this.objectTypes.find(t => t.value === this.currentType);
                const response = await apiDelete(`${typeConfig.endpoint}/${obj.id}`);
                
                if (response.ok) {
                    await this.fetchObjects(this.pagination.current_page);
                } else {
                    const data = await response.json();
                    alert(data.message || 'Ошибка при удалении объекта');
                }
            } catch (error) {
                console.error('Error deleting object:', error);
                alert('Ошибка при удалении объекта');
            }
        },
        getDataSourceLabel(source) {
            const labels = {
                parser: 'Парсер',
                manual: 'Вручную',
                feed: 'Feed',
                import: 'Импорт',
            };
            return labels[source] || source;
        },
        async checkActuality(obj) {
            if (this.checkingActuality === obj.id) {
                return;
            }
            
            this.checkingActuality = obj.id;
            
            try {
                const typeConfig = this.objectTypes.find(t => t.value === this.currentType);
                
                // Пока проверка актуальности реализована только для blocks
                // Для других типов нужно будет добавить endpoints
                if (this.currentType !== 'blocks') {
                    alert('Проверка актуальности пока доступна только для блоков (квартир)');
                    this.checkingActuality = null;
                    return;
                }
                
                const response = await apiPost(`/blocks/${obj.id}/check-actuality`, {
                    update: true,
                });
                const data = await response.json();
                
                if (response.ok) {
                    if (data.data && data.data.actual) {
                        alert('✅ Данные актуальны');
                    } else if (data.data && data.data.updated) {
                        const changesText = data.data.changes && data.data.changes.length > 0 
                            ? data.data.changes.map(c => c.field || c).join(', ')
                            : 'Нет данных о изменениях';
                        alert('✅ Данные обновлены. Обнаружены изменения: ' + changesText);
                        await this.fetchObjects(this.pagination.current_page);
                    } else if (data.data && data.data.changes && data.data.changes.length > 0) {
                        const changesText = data.data.changes.map(c => c.field || c).join(', ');
                        alert('⚠️ Обнаружены изменения: ' + changesText);
                    } else {
                        alert(data.message || 'Проверка завершена');
                    }
                } else {
                    alert(data.message || 'Ошибка при проверке актуальности');
                }
            } catch (error) {
                console.error('Error checking actuality:', error);
                alert('Ошибка при проверке актуальности: ' + (error.message || 'Неизвестная ошибка'));
            } finally {
                this.checkingActuality = null;
            }
        },
    },
};
</script>

