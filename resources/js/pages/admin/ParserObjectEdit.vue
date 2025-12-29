<template>
    <div class="parser-object-edit space-y-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <button
                    @click="$router.go(-1)"
                    class="mb-2 text-sm text-muted-foreground hover:text-foreground flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Назад
                </button>
                <h1 class="text-3xl font-semibold text-foreground">Редактирование объекта</h1>
                <p class="text-muted-foreground mt-1">{{ object?.name || 'Загрузка...' }}</p>
            </div>
            <div class="flex gap-2">
                <button
                    @click="goToView"
                    class="h-11 px-6 bg-muted hover:bg-muted/80 text-foreground rounded-lg transition-colors"
                >
                    Просмотр
                </button>
                <button
                    @click="saveObject"
                    :disabled="saving || !hasChanges"
                    class="h-11 px-6 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ saving ? 'Сохранение...' : 'Сохранить' }}
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка объекта...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Edit Form -->
        <div v-if="!loading && object" class="space-y-6">
            <!-- Основная информация -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Основная информация</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Название *</label>
                        <input
                            v-model="formData.name"
                            type="text"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Адрес</label>
                        <input
                            v-model="formData.address"
                            type="text"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Город</label>
                        <select
                            v-model="formData.city_id"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option :value="null">Выберите город</option>
                            <option v-for="city in cities" :key="city.id" :value="city.id">
                                {{ city.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Застройщик</label>
                        <select
                            v-model="formData.builder_id"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option :value="null">Выберите застройщика</option>
                            <option v-for="builder in builders" :key="builder.id" :value="builder.id">
                                {{ builder.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">GUID</label>
                        <input
                            v-model="formData.guid"
                            type="text"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">External ID</label>
                        <input
                            v-model="formData.external_id"
                            type="text"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                </div>
            </div>

            <!-- Координаты -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Координаты</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Широта</label>
                        <input
                            v-model.number="formData.latitude"
                            type="number"
                            step="0.000001"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Долгота</label>
                        <input
                            v-model.number="formData.longitude"
                            type="number"
                            step="0.000001"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                </div>
            </div>

            <!-- Цены -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Цены</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Минимальная цена (копейки)</label>
                        <input
                            v-model.number="formData.min_price"
                            type="number"
                            min="0"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ formData.min_price ? (formData.min_price / 100).toLocaleString('ru-RU') + ' ₽' : '' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Максимальная цена (копейки)</label>
                        <input
                            v-model.number="formData.max_price"
                            type="number"
                            min="0"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ formData.max_price ? (formData.max_price / 100).toLocaleString('ru-RU') + ' ₽' : '' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Статистика -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Статистика</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Всего квартир</label>
                        <input
                            v-model.number="formData.apartments_count"
                            type="number"
                            min="0"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">На просмотр</label>
                        <input
                            v-model.number="formData.view_apartments_count"
                            type="number"
                            min="0"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Эксклюзивных</label>
                        <input
                            v-model.number="formData.exclusive_apartments_count"
                            type="number"
                            min="0"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                </div>
            </div>

            <!-- Статусы -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Статусы</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Статус</label>
                        <input
                            v-model.number="formData.status"
                            type="number"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Источник данных</label>
                        <select
                            v-model="formData.data_source"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="parser">Парсер</option>
                            <option value="manual">Вручную</option>
                            <option value="feed">Feed</option>
                            <option value="import">Импорт</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            v-model="formData.is_active"
                            type="checkbox"
                            class="w-5 h-5 rounded border-border text-accent focus:ring-accent/20"
                        />
                        <label class="text-sm font-medium text-foreground">Активен</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            v-model="formData.is_suite"
                            type="checkbox"
                            class="w-5 h-5 rounded border-border text-accent focus:ring-accent/20"
                        />
                        <label class="text-sm font-medium text-foreground">Сьют</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            v-model="formData.is_exclusive"
                            type="checkbox"
                            class="w-5 h-5 rounded border-border text-accent focus:ring-accent/20"
                        />
                        <label class="text-sm font-medium text-foreground">Эксклюзив</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            v-model="formData.is_marked"
                            type="checkbox"
                            class="w-5 h-5 rounded border-border text-accent focus:ring-accent/20"
                        />
                        <label class="text-sm font-medium text-foreground">Помечен</label>
                    </div>
                </div>
            </div>

            <!-- Сроки -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Сроки</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Срок сдачи</label>
                        <input
                            v-model="formData.deadline"
                            type="text"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Дата сдачи</label>
                        <input
                            v-model="formData.deadline_date"
                            type="date"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-2">Отделка</label>
                        <input
                            v-model="formData.finishing"
                            type="text"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { apiGet, apiPut } from '../../utils/api';

export default {
    name: 'ParserObjectEdit',
    data() {
        return {
            loading: false,
            saving: false,
            error: null,
            object: null,
            objectType: 'blocks',
            objectId: null,
            cities: [],
            builders: [],
            formData: {
                name: '',
                address: '',
                city_id: null,
                builder_id: null,
                guid: '',
                external_id: '',
                latitude: null,
                longitude: null,
                min_price: null,
                max_price: null,
                apartments_count: null,
                view_apartments_count: null,
                exclusive_apartments_count: null,
                status: null,
                data_source: 'parser',
                is_active: true,
                is_suite: false,
                is_exclusive: false,
                is_marked: false,
                deadline: '',
                deadline_date: '',
                finishing: '',
            },
            originalData: {},
        };
    },
    computed: {
        hasChanges() {
            return JSON.stringify(this.formData) !== JSON.stringify(this.originalData);
        },
    },
    async mounted() {
        this.objectType = this.$route.params.type || 'blocks';
        this.objectId = this.$route.params.id;
        await Promise.all([
            this.fetchObject(),
            this.fetchCities(),
            this.fetchBuilders(),
        ]);
    },
    methods: {
        async fetchObject() {
            this.loading = true;
            this.error = null;
            
            try {
                const endpoints = {
                    blocks: '/blocks',
                    parkings: '/parkings',
                    villages: '/villages',
                    plots: '/plots',
                    'commercial-blocks': '/commercial-blocks',
                    'commercial-premises': '/commercial-premises',
                };
                
                const endpoint = endpoints[this.objectType] || '/blocks';
                const response = await apiGet(`${endpoint}/${this.objectId}`);
                const data = await response.json();
                
                if (response.ok) {
                    this.object = data.data || data;
                    this.populateFormData(this.object);
                } else {
                    this.error = data.message || 'Ошибка при загрузке объекта';
                }
            } catch (error) {
                console.error('Error fetching object:', error);
                this.error = 'Ошибка при загрузке объекта';
            } finally {
                this.loading = false;
            }
        },
        populateFormData(obj) {
            this.formData = {
                name: obj.name || '',
                address: obj.address || '',
                city_id: obj.city?.id || null,
                builder_id: obj.builder?.id || null,
                guid: obj.guid || '',
                external_id: obj.external_id || '',
                latitude: obj.coordinates?.latitude || null,
                longitude: obj.coordinates?.longitude || null,
                min_price: obj.prices?.min || null,
                max_price: obj.prices?.max || null,
                apartments_count: obj.stats?.apartments_count || null,
                view_apartments_count: obj.stats?.view_apartments_count || null,
                exclusive_apartments_count: obj.stats?.exclusive_apartments_count || null,
                status: obj.status || null,
                data_source: obj.data_source || 'parser',
                is_active: obj.is_active ?? true,
                is_suite: obj.is_suite ?? false,
                is_exclusive: obj.is_exclusive ?? false,
                is_marked: obj.is_marked ?? false,
                deadline: obj.deadline || '',
                deadline_date: obj.deadline_date ? new Date(obj.deadline_date).toISOString().split('T')[0] : '',
                finishing: obj.finishing || '',
            };
            this.originalData = JSON.parse(JSON.stringify(this.formData));
        },
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
        async fetchBuilders() {
            try {
                const response = await apiGet('/builders');
                const data = await response.json();
                if (data.data) {
                    this.builders = Array.isArray(data.data) ? data.data : [];
                }
            } catch (error) {
                console.error('Error fetching builders:', error);
            }
        },
        async saveObject() {
            if (!this.hasChanges) {
                return;
            }
            
            this.saving = true;
            this.error = null;
            
            try {
                const endpoints = {
                    blocks: '/blocks',
                    parkings: '/parkings',
                    villages: '/villages',
                    plots: '/plots',
                    'commercial-blocks': '/commercial-blocks',
                    'commercial-premises': '/commercial-premises',
                };
                
                const endpoint = endpoints[this.objectType] || '/blocks';
                
                // Подготовка данных для отправки
                const payload = { ...this.formData };
                
                // Удаляем null значения
                Object.keys(payload).forEach(key => {
                    if (payload[key] === null || payload[key] === '') {
                        delete payload[key];
                    }
                });
                
                const response = await apiPut(`${endpoint}/${this.objectId}`, payload);
                const data = await response.json();
                
                if (response.ok) {
                    alert('Объект успешно обновлен');
                    this.originalData = JSON.parse(JSON.stringify(this.formData));
                    await this.fetchObject();
                } else {
                    this.error = data.message || 'Ошибка при сохранении объекта';
                    alert(this.error);
                }
            } catch (error) {
                console.error('Error saving object:', error);
                this.error = 'Ошибка при сохранении объекта';
                alert(this.error);
            } finally {
                this.saving = false;
            }
        },
        goToView() {
            this.$router.push({
                name: 'admin.parser.object.view',
                params: {
                    type: this.objectType,
                    id: this.objectId,
                },
            });
        },
    },
};
</script>

