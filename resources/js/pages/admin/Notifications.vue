<template>
    <div class="notifications-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Уведомления</h1>
                <p class="text-muted-foreground mt-1">Просмотр всех уведомлений</p>
            </div>
        </div>

        <!-- Фильтры -->
        <div class="bg-card rounded-lg border border-border p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Поиск -->
                <div class="md:col-span-2">
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground">🔍</span>
                        <input
                            type="text"
                            v-model="filters.search"
                            @input="handleSearch"
                            placeholder="Поиск по заголовку и сообщению..."
                            class="w-full h-10 pl-9 pr-3 border border-border rounded bg-background text-sm"
                        />
                    </div>
                </div>

                <!-- Фильтр по статусу -->
                <div>
                    <select
                        v-model="filters.read"
                        @change="handleFilterChange"
                        class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                    >
                        <option :value="null">Все уведомления</option>
                        <option :value="false">Непрочитанные</option>
                        <option :value="true">Прочитанные</option>
                    </select>
                </div>

                <!-- Фильтр по типу -->
                <div>
                    <select
                        v-model="filters.type"
                        @change="handleFilterChange"
                        class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                    >
                        <option value="">Все типы</option>
                        <option value="info">Информация</option>
                        <option value="success">Успех</option>
                        <option value="warning">Предупреждение</option>
                        <option value="error">Ошибка</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка уведомлений...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Notifications List -->
        <div v-if="!loading && notifications.length > 0" class="space-y-3">
            <div
                v-for="notification in notifications"
                :key="notification.id"
                :class="[
                    'bg-card rounded-lg border p-4 transition-colors',
                    !notification.read ? 'border-accent bg-accent/5' : 'border-border'
                ]"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <h3 class="font-semibold text-foreground">{{ notification.title }}</h3>
                            <span
                                v-if="!notification.read"
                                class="h-2 w-2 bg-accent rounded-full flex-shrink-0"
                                title="Непрочитанное"
                            ></span>
                            <span
                                :class="[
                                    'px-2 py-1 text-xs rounded',
                                    getTypeClass(notification.type)
                                ]"
                            >
                                {{ getTypeLabel(notification.type) }}
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground mb-2">{{ notification.message }}</p>
                        <div class="flex items-center gap-4 text-xs text-muted-foreground">
                            <span>{{ formatDate(notification.created_at) }}</span>
                            <span v-if="notification.read_at">
                                Прочитано: {{ formatDate(notification.read_at) }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button
                            v-if="!notification.read"
                            @click="markAsRead(notification.id)"
                            class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors"
                            title="Отметить как прочитанное"
                        >
                            Прочитано
                        </button>
                        <button
                            @click="deleteNotification(notification.id)"
                            class="px-3 py-1 text-xs bg-red-500 hover:bg-red-600 text-white rounded transition-colors"
                            title="Удалить"
                        >
                            Удалить
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && notifications.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Уведомления не найдены</p>
        </div>

        <!-- Пагинация -->
        <div v-if="pagination && pagination.last_page > 1" class="flex flex-col sm:flex-row items-center justify-between pt-6 border-t border-border gap-4">
            <div class="text-sm text-muted-foreground">
                <span>
                    Показано {{ pagination.from }}-{{ pagination.to }} из {{ pagination.total }}
                </span>
            </div>
            <div class="flex gap-2 items-center">
                <button
                    @click="handlePageChange(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="px-3 py-2 rounded-md border border-border bg-background hover:bg-accent/10 disabled:opacity-50 disabled:cursor-not-allowed text-sm transition-colors"
                >
                    ← Назад
                </button>
                
                <!-- Номера страниц -->
                <div class="flex gap-1">
                    <button
                        v-for="pageNum in getPageNumbers(pagination.current_page, pagination.last_page)"
                        :key="pageNum"
                        @click="handlePageChange(pageNum)"
                        :class="[
                            'px-3 py-2 rounded-md border text-sm transition-colors min-w-[40px]',
                            pageNum === pagination.current_page
                                ? 'bg-accent text-accent-foreground border-accent font-semibold'
                                : 'border-border bg-background hover:bg-accent/10'
                        ]"
                    >
                        {{ pageNum }}
                    </button>
                </div>
                
                <button
                    @click="handlePageChange(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="px-3 py-2 rounded-md border border-border bg-background hover:bg-accent/10 disabled:opacity-50 disabled:cursor-not-allowed text-sm transition-colors"
                >
                    Вперед →
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { apiGet, apiPost, apiDelete } from '../../utils/api'
import Swal from 'sweetalert2'

export default {
    name: 'Notifications',
    setup() {
        const loading = ref(false)
        const error = ref(null)
        const notifications = ref([])
        const pagination = ref(null)
        const filters = ref({
            search: '',
            read: null,
            type: ''
        })
        const currentPage = ref(1)
        const perPage = ref(20)
        const searchTimeout = ref(null)

        const fetchNotifications = async (page = 1) => {
            loading.value = true
            error.value = null
            try {
                const params = new URLSearchParams()
                params.append('page', page)
                params.append('per_page', perPage.value)
                
                if (filters.value.search) {
                    params.append('search', filters.value.search)
                }
                if (filters.value.read !== null) {
                    params.append('read', filters.value.read ? '1' : '0')
                }
                if (filters.value.type) {
                    params.append('type', filters.value.type)
                }

                // Используем прямой путь к API, так как notifications не в v1
                const token = localStorage.getItem('token')
                const headers = {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
                if (token) {
                    headers['Authorization'] = `Bearer ${token}`
                }
                
                const response = await fetch(`/api/notifications/all?${params.toString()}`, {
                    method: 'GET',
                    headers,
                })
                
                if (!response.ok) {
                    throw new Error('Ошибка загрузки уведомлений')
                }

                const data = await response.json()
                
                notifications.value = data.data || []
                pagination.value = {
                    current_page: data.current_page || 1,
                    last_page: data.last_page || 1,
                    per_page: data.per_page || perPage.value,
                    total: data.total || 0,
                    from: data.from || 0,
                    to: data.to || 0
                }
                currentPage.value = data.current_page || 1
            } catch (err) {
                error.value = err.message || 'Ошибка загрузки уведомлений'
            } finally {
                loading.value = false
            }
        }

        const handleSearch = () => {
            if (searchTimeout.value) {
                clearTimeout(searchTimeout.value)
            }
            searchTimeout.value = setTimeout(() => {
                currentPage.value = 1
                fetchNotifications(1)
            }, 500)
        }

        const handleFilterChange = () => {
            currentPage.value = 1
            fetchNotifications(1)
        }

        const handlePageChange = (page) => {
            fetchNotifications(page)
        }

        const markAsRead = async (id) => {
            try {
                const token = localStorage.getItem('token')
                const headers = {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
                if (token) {
                    headers['Authorization'] = `Bearer ${token}`
                }
                
                const response = await fetch(`/api/notifications/${id}/read`, {
                    method: 'POST',
                    headers,
                })
                
                if (response.ok) {
                    await fetchNotifications(currentPage.value)
                }
            } catch (err) {
                console.error('Error marking notification as read:', err)
            }
        }

        const deleteNotification = async (id) => {
            const result = await Swal.fire({
                title: 'Удалить уведомление?',
                text: 'Вы уверены, что хотите удалить это уведомление?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Да, удалить',
                cancelButtonText: 'Отмена',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
            })

            if (!result.isConfirmed) return

            try {
                const token = localStorage.getItem('token')
                const headers = {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
                if (token) {
                    headers['Authorization'] = `Bearer ${token}`
                }
                
                const response = await fetch(`/api/notifications/${id}`, {
                    method: 'DELETE',
                    headers,
                })
                
                if (!response.ok) {
                    throw new Error('Ошибка удаления уведомления')
                }

                await Swal.fire({
                    title: 'Уведомление удалено',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                })

                await fetchNotifications(currentPage.value)
            } catch (err) {
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка удаления уведомления',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
            }
        }

        const formatDate = (dateString) => {
            if (!dateString) return ''
            const date = new Date(dateString)
            return date.toLocaleString('ru-RU', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
        }

        const getTypeLabel = (type) => {
            const labels = {
                'info': 'Информация',
                'success': 'Успех',
                'warning': 'Предупреждение',
                'error': 'Ошибка'
            }
            return labels[type] || type
        }

        const getTypeClass = (type) => {
            const classes = {
                'info': 'bg-blue-500/10 text-blue-500',
                'success': 'bg-green-500/10 text-green-500',
                'warning': 'bg-yellow-500/10 text-yellow-500',
                'error': 'bg-red-500/10 text-red-500'
            }
            return classes[type] || 'bg-muted text-muted-foreground'
        }

        const getPageNumbers = (currentPage, lastPage) => {
            const pages = []
            const maxVisible = 5
            
            if (lastPage <= maxVisible) {
                for (let i = 1; i <= lastPage; i++) {
                    pages.push(i)
                }
            } else {
                if (currentPage <= 3) {
                    for (let i = 1; i <= 5; i++) {
                        pages.push(i)
                    }
                } else if (currentPage >= lastPage - 2) {
                    for (let i = lastPage - 4; i <= lastPage; i++) {
                        pages.push(i)
                    }
                } else {
                    for (let i = currentPage - 2; i <= currentPage + 2; i++) {
                        pages.push(i)
                    }
                }
            }
            
            return pages
        }

        onMounted(() => {
            fetchNotifications(1)
        })

        return {
            loading,
            error,
            notifications,
            pagination,
            filters,
            handleSearch,
            handleFilterChange,
            handlePageChange,
            markAsRead,
            deleteNotification,
            formatDate,
            getTypeLabel,
            getTypeClass,
            getPageNumbers
        }
    }
}
</script>

