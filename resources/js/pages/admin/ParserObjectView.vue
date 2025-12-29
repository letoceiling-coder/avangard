<template>
    <div class="parser-object-view space-y-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <button
                    @click="$router.go(-1)"
                    class="mb-2 text-sm text-muted-foreground hover:text-foreground flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Назад к списку
                </button>
                <h1 class="text-3xl font-semibold text-foreground">{{ object?.name || 'Загрузка...' }}</h1>
                <p class="text-muted-foreground mt-1">Просмотр объекта парсера</p>
            </div>
            <div class="flex gap-2">
                <button
                    @click="goToEdit"
                    class="h-11 px-6 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors"
                >
                    Редактировать
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

        <!-- Object Details -->
        <div v-if="!loading && object" class="space-y-6">
            <!-- Изображения -->
            <div v-if="(object.main_image && getImageUrl(object.main_image)) || (object.images && object.images.length > 0 && hasValidImages(object.images))" class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Изображения</h2>
                
                <!-- Главное изображение -->
                <div v-if="object.main_image && getImageUrl(object.main_image)" class="mb-6">
                    <label class="block text-sm font-medium text-muted-foreground mb-2">Главное изображение</label>
                    <div class="relative w-full max-w-2xl rounded-lg overflow-hidden border border-border">
                        <img
                            :src="getImageUrl(object.main_image)"
                            :alt="object.main_image.alt || object.name"
                            class="w-full h-auto object-cover"
                            @error="handleImageError"
                        />
                    </div>
                </div>
                
                <!-- Галерея изображений -->
                <div v-if="object.images && object.images.length > 0 && hasValidImages(object.images)">
                    <label class="block text-sm font-medium text-muted-foreground mb-2">
                        Галерея ({{ validImagesCount }})
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div
                            v-for="image in object.images.filter(img => getImageUrl(img))"
                            :key="image.id"
                            class="relative aspect-video rounded-lg overflow-hidden border border-border cursor-pointer hover:border-accent transition-colors group"
                            @click="openImageModal(image)"
                        >
                            <img
                                :src="getImageUrl(image, true)"
                                :alt="image.alt || object.name"
                                class="w-full h-full object-cover"
                                @error="handleImageError"
                            />
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                                <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"></path>
                                </svg>
                            </div>
                            <div v-if="image.is_main" class="absolute top-2 right-2 bg-accent text-white text-xs px-2 py-1 rounded">
                                Главное
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Основная информация -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Основная информация</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">ID</label>
                        <p class="text-foreground">{{ object.id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">GUID</label>
                        <p class="text-foreground">{{ object.guid || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Название</label>
                        <p class="text-foreground font-medium">{{ object.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Адрес</label>
                        <p class="text-foreground">{{ object.address || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Город</label>
                        <p class="text-foreground">{{ object.city?.name || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Регион</label>
                        <p class="text-foreground">{{ object.region?.name || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Локация</label>
                        <p class="text-foreground">{{ object.location?.name || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Застройщик</label>
                        <p class="text-foreground">{{ object.builder?.name || '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Координаты и местоположение -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Местоположение</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Широта</label>
                        <p class="text-foreground">{{ object.coordinates?.latitude || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Долгота</label>
                        <p class="text-foreground">{{ object.coordinates?.longitude || '-' }}</p>
                    </div>
                    <div v-if="object.subways && object.subways.length > 0" class="md:col-span-2">
                        <label class="block text-sm font-medium text-muted-foreground mb-2">Метро</label>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="subway in object.subways"
                                :key="subway.id"
                                class="px-3 py-1 bg-muted rounded-md text-sm"
                            >
                                {{ subway.name || subway.subway_line?.name || '-' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Цены -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Цены</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Минимальная цена</label>
                        <p class="text-foreground">{{ object.prices?.min_formatted || '-' }}</p>
                        <p class="text-xs text-muted-foreground mt-1">{{ object.prices?.min ? (object.prices.min / 100).toLocaleString('ru-RU') + ' ₽' : '' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Максимальная цена</label>
                        <p class="text-foreground">{{ object.prices?.max_formatted || '-' }}</p>
                        <p class="text-xs text-muted-foreground mt-1">{{ object.prices?.max ? (object.prices.max / 100).toLocaleString('ru-RU') + ' ₽' : '' }}</p>
                    </div>
                </div>
            </div>

            <!-- Статистика -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Статистика</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Всего квартир</label>
                        <p class="text-foreground text-2xl font-semibold">{{ object.stats?.apartments_count || 0 }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">На просмотр</label>
                        <p class="text-foreground text-2xl font-semibold">{{ object.stats?.view_apartments_count || 0 }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Эксклюзивных</label>
                        <p class="text-foreground text-2xl font-semibold">{{ object.stats?.exclusive_apartments_count || 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- Статусы -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Статусы</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Статус</label>
                        <p class="text-foreground">{{ object.status || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Активен</label>
                        <span :class="[
                            'px-2 py-1 text-xs rounded-md',
                            object.is_active ? 'bg-green-500/10 text-green-500' : 'bg-red-500/10 text-red-500'
                        ]">
                            {{ object.is_active ? 'Активен' : 'Неактивен' }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Сьют</label>
                        <span :class="[
                            'px-2 py-1 text-xs rounded-md',
                            object.is_suite ? 'bg-blue-500/10 text-blue-500' : 'bg-gray-500/10 text-gray-500'
                        ]">
                            {{ object.is_suite ? 'Да' : 'Нет' }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Эксклюзив</label>
                        <span :class="[
                            'px-2 py-1 text-xs rounded-md',
                            object.is_exclusive ? 'bg-purple-500/10 text-purple-500' : 'bg-gray-500/10 text-gray-500'
                        ]">
                            {{ object.is_exclusive ? 'Да' : 'Нет' }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Помечен</label>
                        <span :class="[
                            'px-2 py-1 text-xs rounded-md',
                            object.is_marked ? 'bg-yellow-500/10 text-yellow-500' : 'bg-gray-500/10 text-gray-500'
                        ]">
                            {{ object.is_marked ? 'Да' : 'Нет' }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Источник данных</label>
                        <span :class="[
                            'px-2 py-1 text-xs rounded-md',
                            object.data_source === 'parser' ? 'bg-blue-500/10 text-blue-500' : 'bg-gray-500/10 text-gray-500'
                        ]">
                            {{ getDataSourceLabel(object.data_source) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Сроки -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Сроки</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Срок сдачи</label>
                        <p class="text-foreground">{{ object.deadline || '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Дата сдачи</label>
                        <p class="text-foreground">{{ object.deadline_date ? new Date(object.deadline_date).toLocaleDateString('ru-RU') : '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Отделка</label>
                        <p class="text-foreground">{{ object.finishing || '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Метаданные -->
            <div v-if="object.metadata || object.advantages || object.payment_types || object.contract_types" class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Дополнительная информация</h2>
                <div class="space-y-4">
                    <div v-if="object.advantages && object.advantages.length > 0">
                        <label class="block text-sm font-medium text-muted-foreground mb-2">Преимущества</label>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="(advantage, index) in object.advantages"
                                :key="index"
                                class="px-3 py-1 bg-muted rounded-md text-sm"
                            >
                                {{ advantage }}
                            </span>
                        </div>
                    </div>
                    <div v-if="object.payment_types && object.payment_types.length > 0">
                        <label class="block text-sm font-medium text-muted-foreground mb-2">Типы оплаты</label>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="(type, index) in object.payment_types"
                                :key="index"
                                class="px-3 py-1 bg-muted rounded-md text-sm"
                            >
                                {{ type }}
                            </span>
                        </div>
                    </div>
                    <div v-if="object.contract_types && object.contract_types.length > 0">
                        <label class="block text-sm font-medium text-muted-foreground mb-2">Типы договоров</label>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="(type, index) in object.contract_types"
                                :key="index"
                                class="px-3 py-1 bg-muted rounded-md text-sm"
                            >
                                {{ type }}
                            </span>
                        </div>
                    </div>
                    <div v-if="object.metadata">
                        <label class="block text-sm font-medium text-muted-foreground mb-2">Метаданные</label>
                        <pre class="p-4 bg-muted rounded-lg text-xs overflow-auto">{{ JSON.stringify(object.metadata, null, 2) }}</pre>
                    </div>
                </div>
            </div>

            <!-- Временные метки -->
            <div class="bg-card rounded-lg border border-border p-6">
                <h2 class="text-xl font-semibold text-foreground mb-4">Временные метки</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Создано</label>
                        <p class="text-foreground">{{ object.created_at ? new Date(object.created_at).toLocaleString('ru-RU') : '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Обновлено</label>
                        <p class="text-foreground">{{ object.updated_at ? new Date(object.updated_at).toLocaleString('ru-RU') : '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Спарсено</label>
                        <p class="text-foreground">{{ object.parsed_at ? new Date(object.parsed_at).toLocaleString('ru-RU') : '-' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-1">Последняя синхронизация</label>
                        <p class="text-foreground">{{ object.last_synced_at ? new Date(object.last_synced_at).toLocaleString('ru-RU') : '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Modal -->
        <div
            v-if="selectedImage"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
            @click="closeImageModal"
        >
            <div class="relative max-w-7xl max-h-full">
                <button
                    @click="closeImageModal"
                    class="absolute top-4 right-4 z-10 w-10 h-10 bg-black/50 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <img
                    :src="getImageUrl(selectedImage)"
                    :alt="selectedImage.alt || object?.name"
                    class="max-w-full max-h-[90vh] object-contain rounded-lg"
                    @error="handleImageError"
                />
                <div v-if="selectedImage.alt || selectedImage.title" class="mt-4 text-center text-white">
                    <p v-if="selectedImage.title" class="font-semibold">{{ selectedImage.title }}</p>
                    <p v-if="selectedImage.alt" class="text-sm text-white/80">{{ selectedImage.alt }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { apiGet } from '../../utils/api';

export default {
    name: 'ParserObjectView',
    data() {
        return {
            loading: false,
            error: null,
            object: null,
            objectType: 'blocks',
            objectId: null,
            selectedImage: null,
        };
    },
    async mounted() {
        this.objectType = this.$route.params.type || 'blocks';
        this.objectId = this.$route.params.id;
        await this.fetchObject();
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
        goToEdit() {
            this.$router.push({
                name: 'admin.parser.object.edit',
                params: {
                    type: this.objectType,
                    id: this.objectId,
                },
            });
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
        openImageModal(image) {
            this.selectedImage = image;
        },
        closeImageModal() {
            this.selectedImage = null;
        },
        getImageUrl(image, thumbnail = false) {
            if (!image) return null;
            
            if (thumbnail) {
                return image.url_thumbnail || image.thumbnail_url || image.url_full || image.full_url || image.path || null;
            }
            
            return image.url_full || image.full_url || image.url_thumbnail || image.thumbnail_url || image.path || null;
        },
        hasValidImages(images) {
            if (!images || !Array.isArray(images)) return false;
            return images.some(img => this.getImageUrl(img));
        },
        get validImagesCount() {
            if (!this.object?.images || !Array.isArray(this.object.images)) return 0;
            return this.object.images.filter(img => this.getImageUrl(img)).length;
        },
        handleImageError(event) {
            // Заменяем изображение на placeholder при ошибке загрузки
            event.target.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="300"%3E%3Crect width="400" height="300" fill="%23e5e7eb"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%239ca3af" font-family="sans-serif" font-size="16"%3EИзображение не найдено%3C/text%3E%3C/svg%3E';
        },
    },
};
</script>

