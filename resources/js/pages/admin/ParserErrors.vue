<template>
    <div class="parser-errors-page space-y-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Ошибки парсера</h1>
                <p class="text-muted-foreground mt-1">Просмотр и управление ошибками парсера</p>
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
                        @input="debouncedFetchErrors"
                        type="text"
                        placeholder="Сообщение или URL..."
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                    />
                </div>
                
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Статус</label>
                    <select
                        v-model="filters.status"
                        @change="fetchErrors"
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                    >
                        <option value="">Все статусы</option>
                        <option value="pending">Ожидает</option>
                        <option value="resolved">Исправлено</option>
                        <option value="ignored">Игнорировано</option>
                    </select>
                </div>
                
                <!-- Error Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Тип ошибки</label>
                    <select
                        v-model="filters.error_type"
                        @change="fetchErrors"
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                    >
                        <option value="">Все типы</option>
                        <option value="api_error">API ошибка</option>
                        <option value="validation_error">Ошибка валидации</option>
                        <option value="parse_error">Ошибка парсинга</option>
                        <option value="network_error">Сетевая ошибка</option>
                        <option value="unknown">Неизвестная</option>
                    </select>
                </div>
                
                <!-- Date Filter -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Период</label>
                    <select
                        v-model="filters.date_range"
                        @change="fetchErrors"
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                    >
                        <option value="">Все время</option>
                        <option value="today">Сегодня</option>
                        <option value="week">Неделя</option>
                        <option value="month">Месяц</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка ошибок...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Errors List -->
        <div v-if="!loading && errors.length > 0" class="space-y-4">
            <div
                v-for="err in errors"
                :key="err.id"
                :class="[
                    'bg-card rounded-lg border p-6',
                    err.status === 'resolved' ? 'border-green-500/20' : 'border-border'
                ]"
            >
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-lg font-semibold text-foreground">
                                {{ err.error_type || 'Неизвестная ошибка' }}
                            </h3>
                            <span :class="[
                                'px-2 py-1 text-xs rounded-md',
                                err.status === 'resolved' ? 'bg-green-500/10 text-green-500' :
                                err.status === 'ignored' ? 'bg-gray-500/10 text-gray-500' :
                                'bg-red-500/10 text-red-500'
                            ]">
                                {{ getStatusLabel(err.status) }}
                            </span>
                        </div>
                        <p class="text-sm text-foreground mb-2">{{ err.message || 'Нет сообщения' }}</p>
                        <p class="text-xs text-muted-foreground mb-4">
                            {{ new Date(err.created_at).toLocaleString('ru-RU') }}
                        </p>
                        
                        <div v-if="err.url" class="text-sm text-muted-foreground mb-2">
                            <strong>URL:</strong> {{ err.url }}
                        </div>
                        <div v-if="err.context" class="text-sm text-muted-foreground">
                            <details>
                                <summary class="cursor-pointer hover:text-foreground">Детали</summary>
                                <pre class="mt-2 p-2 bg-muted/30 rounded text-xs overflow-auto">{{ JSON.stringify(err.context, null, 2) }}</pre>
                            </details>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 ml-4">
                        <button
                            v-if="err.status === 'pending'"
                            @click="updateErrorStatus(err, 'resolved')"
                            class="px-3 py-1 text-xs bg-green-500 hover:bg-green-600 text-white rounded transition-colors"
                        >
                            Исправить
                        </button>
                        <button
                            v-if="err.status === 'pending'"
                            @click="updateErrorStatus(err, 'ignored')"
                            class="px-3 py-1 text-xs bg-gray-500 hover:bg-gray-600 text-white rounded transition-colors"
                        >
                            Игнорировать
                        </button>
                        <button
                            @click="deleteError(err)"
                            class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition-colors"
                        >
                            Удалить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && errors.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Ошибки не найдены</p>
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
import { apiGet, apiPut, apiDelete } from '../../utils/api';

export default {
    name: 'ParserErrors',
    data() {
        return {
            loading: false,
            error: null,
            errors: [],
            pagination: null,
            filters: {
                search: '',
                status: '',
                error_type: '',
                date_range: '',
            },
            debounceTimer: null,
        };
    },
    async mounted() {
        await this.fetchErrors();
    },
    methods: {
        async fetchErrors(page = 1) {
            this.loading = true;
            this.error = null;
            
            try {
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
                
                const response = await apiGet('/parser-errors', params);
                const data = await response.json();
                
                if (response.ok) {
                    this.errors = data.data || [];
                    this.pagination = {
                        current_page: data.current_page || 1,
                        last_page: data.last_page || 1,
                        from: data.from || 0,
                        to: data.to || 0,
                        total: data.total || 0,
                    };
                } else {
                    this.error = data.message || 'Ошибка при загрузке ошибок';
                }
            } catch (error) {
                console.error('Error fetching errors:', error);
                this.error = 'Ошибка при загрузке ошибок';
            } finally {
                this.loading = false;
            }
        },
        debouncedFetchErrors() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.fetchErrors();
            }, 500);
        },
        changePage(page) {
            this.fetchErrors(page);
        },
        async updateErrorStatus(error, status) {
            try {
                const response = await apiPut(`/parser-errors/${error.id}`, { status });
                
                if (response.ok) {
                    await this.fetchErrors(this.pagination.current_page);
                } else {
                    const data = await response.json();
                    alert(data.message || 'Ошибка при обновлении статуса');
                }
            } catch (error) {
                console.error('Error updating error status:', error);
                alert('Ошибка при обновлении статуса');
            }
        },
        async deleteError(error) {
            if (!confirm('Вы уверены, что хотите удалить эту ошибку?')) {
                return;
            }
            
            try {
                const response = await apiDelete(`/parser-errors/${error.id}`);
                
                if (response.ok) {
                    await this.fetchErrors(this.pagination.current_page);
                } else {
                    const data = await response.json();
                    alert(data.message || 'Ошибка при удалении ошибки');
                }
            } catch (error) {
                console.error('Error deleting error:', error);
                alert('Ошибка при удалении ошибки');
            }
        },
        getStatusLabel(status) {
            const labels = {
                'pending': 'Ожидает',
                'resolved': 'Исправлено',
                'ignored': 'Игнорировано',
            };
            return labels[status] || status;
        },
    },
};
</script>

