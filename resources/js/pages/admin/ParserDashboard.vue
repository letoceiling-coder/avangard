<template>
    <div class="parser-dashboard-page space-y-6">
        <div class="mb-6">
            <h1 class="text-3xl font-semibold text-foreground">Парсер данных</h1>
            <p class="text-muted-foreground mt-1">Управление парсером данных TrendAgent</p>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-card rounded-lg border border-border p-6">
                <h3 class="text-sm font-medium text-muted-foreground mb-2">Всего объектов</h3>
                <p class="text-2xl font-bold text-foreground">{{ stats.total || 0 }}</p>
            </div>
            <div class="bg-card rounded-lg border border-border p-6">
                <h3 class="text-sm font-medium text-muted-foreground mb-2">Активных расписаний</h3>
                <p class="text-2xl font-bold text-foreground">{{ stats.activeSchedules || 0 }}</p>
            </div>
            <div class="bg-card rounded-lg border border-border p-6">
                <h3 class="text-sm font-medium text-muted-foreground mb-2">Ошибок (24ч)</h3>
                <p class="text-2xl font-bold text-foreground">{{ stats.recentErrors || 0 }}</p>
            </div>
            <div class="bg-card rounded-lg border border-border p-6">
                <h3 class="text-sm font-medium text-muted-foreground mb-2">Последний запуск</h3>
                <p class="text-sm font-medium text-foreground">
                    {{ stats.lastRun ? new Date(stats.lastRun).toLocaleString('ru-RU') : 'Не запускался' }}
                </p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-card rounded-lg border border-border p-6">
            <h2 class="text-lg font-semibold mb-4">Быстрые действия</h2>
            <div class="flex flex-wrap gap-4">
                <button
                    @click="runParser"
                    :disabled="running"
                    class="px-4 py-2 bg-purple-500 hover:bg-purple-600 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors font-medium"
                >
                    <span v-if="running">Запуск парсера...</span>
                    <span v-else>🚀 Запустить парсер</span>
                </button>
                <router-link
                    to="/parser/objects"
                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors"
                >
                    Просмотр объектов
                </router-link>
                <router-link
                    to="/parser/schedules"
                    class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors"
                >
                    Управление расписаниями
                </router-link>
                <router-link
                    to="/parser/errors"
                    class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors"
                >
                    Просмотр ошибок
                </router-link>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-card rounded-lg border border-border p-6">
            <h2 class="text-lg font-semibold mb-4">Последняя активность</h2>
            <div v-if="loading" class="text-center py-8 text-muted-foreground">
                Загрузка...
            </div>
            <div v-else-if="recentActivity.length === 0" class="text-center py-8 text-muted-foreground">
                Нет активности
            </div>
            <div v-else class="space-y-2">
                <div
                    v-for="activity in recentActivity"
                    :key="activity.id"
                    class="flex items-center justify-between p-3 bg-muted/30 rounded-lg"
                >
                    <div>
                        <p class="text-sm font-medium text-foreground">{{ activity.message }}</p>
                        <p class="text-xs text-muted-foreground">{{ new Date(activity.created_at).toLocaleString('ru-RU') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { apiGet, apiPost } from '../../utils/api';
import Swal from 'sweetalert2';

export default {
    name: 'ParserDashboard',
    data() {
        return {
            loading: true,
            running: false,
            stats: {
                total: 0,
                activeSchedules: 0,
                recentErrors: 0,
                lastRun: null,
            },
            recentActivity: [],
        };
    },
    async mounted() {
        await this.fetchStats();
    },
    methods: {
        async fetchStats() {
            this.loading = true;
            try {
                // Здесь можно добавить API endpoint для получения статистики
                // Пока используем заглушки
                this.stats = {
                    total: 0,
                    activeSchedules: 0,
                    recentErrors: 0,
                    lastRun: null,
                };
            } catch (error) {
                console.error('Error fetching stats:', error);
            } finally {
                this.loading = false;
            }
        },
        async runParser() {
            if (this.running) return;

            const result = await Swal.fire({
                title: 'Запустить парсер?',
                text: 'Парсер будет запущен для всех активных городов и типов объектов',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Запустить',
                cancelButtonText: 'Отмена',
                confirmButtonColor: '#9333ea',
            });

            if (!result.isConfirmed) {
                return;
            }

            this.running = true;

            try {
                const response = await apiPost('/parser/run', {});

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || 'Ошибка при запуске парсера');
                }

                const data = await response.json();

                await Swal.fire({
                    icon: 'success',
                    title: 'Парсер запущен',
                    text: data.message || 'Парсер успешно запущен в фоновом режиме',
                    timer: 3000,
                    showConfirmButton: false,
                });

                // Обновляем статистику через несколько секунд
                setTimeout(() => {
                    this.fetchStats();
                }, 5000);

            } catch (error) {
                console.error('Error running parser:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Ошибка',
                    text: error.message || 'Не удалось запустить парсер',
                });
            } finally {
                this.running = false;
            }
        },
    },
};
</script>

