<template>
    <div class="parser-schedules-page space-y-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Расписания парсера</h1>
                <p class="text-muted-foreground mt-1">Управление расписаниями автоматического парсинга</p>
            </div>
            <button
                @click="showCreateModal = true"
                class="h-11 px-6 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-2xl shadow-lg shadow-accent/10 inline-flex items-center justify-center gap-2"
            >
                <span>+</span>
                <span>Создать расписание</span>
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка расписаний...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Schedules List -->
        <div v-if="!loading && schedules.length > 0" class="space-y-4">
            <div
                v-for="schedule in schedules"
                :key="schedule.id"
                class="bg-card rounded-lg border border-border p-6"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-lg font-semibold text-foreground">
                                {{ getObjectTypeLabel(schedule.object_type) }}
                            </h3>
                            <span :class="[
                                'px-2 py-1 text-xs rounded-md',
                                schedule.is_active ? 'bg-green-500/10 text-green-500' : 'bg-gray-500/10 text-gray-500'
                            ]">
                                {{ schedule.is_active ? 'Активно' : 'Неактивно' }}
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground mb-4">{{ schedule.description || 'Нет описания' }}</p>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-muted-foreground">Время:</span>
                                <span class="ml-2 text-foreground">{{ schedule.time_from }} - {{ schedule.time_to }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Дни недели:</span>
                                <span class="ml-2 text-foreground">{{ formatDaysOfWeek(schedule.days_of_week) }}</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Последний запуск:</span>
                                <span class="ml-2 text-foreground">
                                    {{ schedule.last_run_at ? new Date(schedule.last_run_at).toLocaleString('ru-RU') : 'Не запускалось' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Статус:</span>
                                <span class="ml-2 text-foreground">{{ schedule.last_run_status || '-' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 ml-4">
                        <button
                            @click="editSchedule(schedule)"
                            class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                        >
                            Редактировать
                        </button>
                        <button
                            @click="toggleSchedule(schedule)"
                            :class="[
                                'px-3 py-1 text-xs rounded transition-colors',
                                schedule.is_active
                                    ? 'bg-yellow-500 hover:bg-yellow-600 text-white'
                                    : 'bg-green-500 hover:bg-green-600 text-white'
                            ]"
                        >
                            {{ schedule.is_active ? 'Деактивировать' : 'Активировать' }}
                        </button>
                        <button
                            @click="deleteSchedule(schedule)"
                            class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition-colors"
                        >
                            Удалить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && schedules.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Расписания не найдены</p>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
            <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold mb-4">
                    {{ showEditModal ? 'Редактировать расписание' : 'Создать расписание' }}
                </h3>
                <form @submit.prevent="saveSchedule" class="space-y-4">
                    <div>
                        <label class="text-sm font-medium mb-1 block">Тип объекта *</label>
                        <select
                            v-model="form.object_type"
                            required
                            class="w-full h-10 px-3 border border-border rounded bg-background"
                        >
                            <option value="">Выберите тип</option>
                            <option value="blocks">Квартиры (Блоки)</option>
                            <option value="parkings">Паркинги</option>
                            <option value="villages">Поселки</option>
                            <option value="plots">Участки</option>
                            <option value="commercial-blocks">Коммерческие объекты</option>
                            <option value="commercial-premises">Коммерческие помещения</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium mb-1 block">Время начала (HH:mm) *</label>
                            <input
                                v-model="form.time_from"
                                type="time"
                                required
                                class="w-full h-10 px-3 border border-border rounded bg-background"
                            />
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">Время окончания (HH:mm) *</label>
                            <input
                                v-model="form.time_to"
                                type="time"
                                required
                                class="w-full h-10 px-3 border border-border rounded bg-background"
                            />
                        </div>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium mb-1 block">Дни недели *</label>
                        <div class="grid grid-cols-7 gap-2">
                            <label
                                v-for="(day, index) in daysOfWeek"
                                :key="index"
                                class="flex items-center gap-2 cursor-pointer p-2 border border-border rounded hover:bg-muted/10"
                            >
                                <input
                                    type="checkbox"
                                    :value="index + 1"
                                    v-model="form.days_of_week"
                                />
                                <span class="text-sm">{{ day }}</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium mb-1 block">Описание</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full px-3 py-2 border border-border rounded bg-background"
                        ></textarea>
                    </div>
                    
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" />
                            <span class="text-sm">Активно</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.check_images" />
                            <span class="text-sm">Проверять изображения</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.force_update" />
                            <span class="text-sm">Принудительное обновление</span>
                        </label>
                    </div>
                    
                    <div class="flex gap-2 pt-4">
                        <button
                            type="button"
                            @click="closeModal"
                            class="flex-1 h-10 px-4 border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors"
                        >
                            Отмена
                        </button>
                        <button
                            type="submit"
                            :disabled="saving"
                            class="flex-1 h-10 px-4 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors disabled:opacity-50"
                        >
                            {{ saving ? 'Сохранение...' : 'Сохранить' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { apiGet, apiPost, apiPut, apiDelete } from '../../utils/api';

export default {
    name: 'ParserSchedules',
    data() {
        return {
            loading: false,
            saving: false,
            error: null,
            schedules: [],
            showCreateModal: false,
            showEditModal: false,
            form: {
                id: null,
                object_type: '',
                time_from: '',
                time_to: '',
                days_of_week: [],
                description: '',
                is_active: true,
                check_images: false,
                force_update: false,
            },
            daysOfWeek: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
        };
    },
    async mounted() {
        await this.fetchSchedules();
    },
    methods: {
        async fetchSchedules() {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await apiGet('/parser-schedules');
                const data = await response.json();
                
                if (response.ok) {
                    this.schedules = data.data || [];
                } else {
                    this.error = data.message || 'Ошибка при загрузке расписаний';
                }
            } catch (error) {
                console.error('Error fetching schedules:', error);
                this.error = 'Ошибка при загрузке расписаний';
            } finally {
                this.loading = false;
            }
        },
        async saveSchedule() {
            this.saving = true;
            
            try {
                const scheduleData = {
                    ...this.form,
                    days_of_week: this.form.days_of_week.map(d => parseInt(d)),
                };
                
                let response;
                if (this.showEditModal) {
                    response = await apiPut(`/parser-schedules/${this.form.id}`, scheduleData);
                } else {
                    response = await apiPost('/parser-schedules', scheduleData);
                }
                
                const data = await response.json();
                
                if (response.ok) {
                    await this.fetchSchedules();
                    this.closeModal();
                } else {
                    alert(data.message || 'Ошибка при сохранении расписания');
                }
            } catch (error) {
                console.error('Error saving schedule:', error);
                alert('Ошибка при сохранении расписания');
            } finally {
                this.saving = false;
            }
        },
        editSchedule(schedule) {
            this.form = {
                id: schedule.id,
                object_type: schedule.object_type,
                time_from: schedule.time_from,
                time_to: schedule.time_to,
                days_of_week: schedule.days_of_week || [],
                description: schedule.description || '',
                is_active: schedule.is_active,
                check_images: schedule.check_images,
                force_update: schedule.force_update,
            };
            this.showEditModal = true;
        },
        async toggleSchedule(schedule) {
            try {
                const response = await apiPut(`/parser-schedules/${schedule.id}`, {
                    is_active: !schedule.is_active,
                });
                
                if (response.ok) {
                    await this.fetchSchedules();
                } else {
                    const data = await response.json();
                    alert(data.message || 'Ошибка при обновлении расписания');
                }
            } catch (error) {
                console.error('Error toggling schedule:', error);
                alert('Ошибка при обновлении расписания');
            }
        },
        async deleteSchedule(schedule) {
            if (!confirm(`Вы уверены, что хотите удалить расписание для "${this.getObjectTypeLabel(schedule.object_type)}"?`)) {
                return;
            }
            
            try {
                const response = await apiDelete(`/parser-schedules/${schedule.id}`);
                
                if (response.ok) {
                    await this.fetchSchedules();
                } else {
                    const data = await response.json();
                    alert(data.message || 'Ошибка при удалении расписания');
                }
            } catch (error) {
                console.error('Error deleting schedule:', error);
                alert('Ошибка при удалении расписания');
            }
        },
        closeModal() {
            this.showCreateModal = false;
            this.showEditModal = false;
            this.form = {
                id: null,
                object_type: '',
                time_from: '',
                time_to: '',
                days_of_week: [],
                description: '',
                is_active: true,
                check_images: false,
                force_update: false,
            };
        },
        getObjectTypeLabel(type) {
            const labels = {
                'blocks': 'Квартиры (Блоки)',
                'parkings': 'Паркинги',
                'villages': 'Поселки',
                'plots': 'Участки',
                'commercial-blocks': 'Коммерческие объекты',
                'commercial-premises': 'Коммерческие помещения',
            };
            return labels[type] || type;
        },
        formatDaysOfWeek(days) {
            if (!days || days.length === 0) return 'Не выбрано';
            const dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
            return days.map(d => dayNames[d - 1]).join(', ');
        },
    },
};
</script>

