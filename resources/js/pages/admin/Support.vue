<template>
    <div class="support-page space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Поддержка</h1>
                <p class="text-muted-foreground mt-1">Управление тикетами поддержки</p>
            </div>
            <button
                @click="showCreateModal = true"
                class="px-4 py-2 bg-accent text-accent-foreground rounded-lg hover:bg-accent/90 transition-colors"
            >
                + Создать тикет
            </button>
        </div>

        <!-- Фильтры -->
        <div class="bg-card rounded-lg border border-border p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <select
                        v-model="filters.status"
                        @change="handleFilterChange"
                        class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                    >
                        <option value="">Все статусы</option>
                        <option value="open">Открыт</option>
                        <option value="in_progress">В работе</option>
                        <option value="closed">Закрыт</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground">🔍</span>
                        <input
                            type="text"
                            v-model="filters.search"
                            @input="handleSearch"
                            placeholder="Поиск по теме..."
                            class="w-full h-10 pl-9 pr-3 border border-border rounded bg-background text-sm"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка тикетов...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Tickets List -->
        <div v-if="!loading && tickets.length > 0" class="space-y-3">
            <div
                v-for="ticket in tickets"
                :key="ticket.id"
                @click="$router.push({ name: 'admin.support.ticket', params: { id: ticket.id } })"
                :class="[
                    'bg-card rounded-lg border p-4 cursor-pointer transition-colors hover:border-accent',
                    'border-border'
                ]"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <h3 class="font-semibold text-foreground">{{ ticket.subject || ticket.theme }}</h3>
                            <StatusBadge :status="ticket.status" />
                        </div>
                        <p class="text-sm text-muted-foreground mb-2">
                            {{ ticket.messages?.[0]?.body || ticket.messages?.[0]?.message || 'Нет сообщений' }}
                            <span v-if="ticket.messages?.[0]?.body || ticket.messages?.[0]?.message">
                                {{ (ticket.messages[0].body || ticket.messages[0].message).length > 100 ? '...' : '' }}
                            </span>
                        </p>
                        <div class="flex items-center gap-4 text-xs text-muted-foreground">
                            <span>Создан: {{ formatDate(ticket.created_at) }}</span>
                            <span v-if="ticket.messages?.length">
                                Сообщений: {{ ticket.messages.length }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && tickets.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Тикеты не найдены</p>
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


        <!-- Create Ticket Modal -->
        <div
            v-if="showCreateModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showCreateModal = false"
        >
            <div class="bg-card rounded-lg border border-border p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <h2 class="text-2xl font-semibold mb-4">Создать тикет</h2>
                <form @submit.prevent="createTicket">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Тема</label>
                            <input
                                v-model="newTicket.theme"
                                type="text"
                                required
                                class="w-full h-10 px-3 border border-border rounded bg-background"
                                placeholder="Введите тему тикета"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Сообщение</label>
                            <textarea
                                v-model="newTicket.message"
                                required
                                rows="5"
                                class="w-full px-3 py-2 border border-border rounded bg-background resize-none"
                                placeholder="Введите сообщение"
                            ></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Вложения</label>
                            <input
                                type="file"
                                @change="handleFileSelect"
                                multiple
                                accept="image/*,.pdf,.doc,.docx,.txt"
                                class="w-full h-10 px-3 border border-border rounded bg-background"
                            />
                            <div v-if="newTicket.attachments.length > 0" class="mt-2 space-y-1">
                                <div
                                    v-for="(file, index) in newTicket.attachments"
                                    :key="index"
                                    class="text-sm text-muted-foreground flex items-center justify-between"
                                >
                                    <span>{{ file.name }}</span>
                                    <button
                                        type="button"
                                        @click="removeAttachment(index)"
                                        class="text-destructive hover:text-destructive/80"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3 mt-6">
                        <button
                            type="submit"
                            :disabled="creating"
                            class="px-4 py-2 bg-accent text-accent-foreground rounded-lg hover:bg-accent/90 disabled:opacity-50 transition-colors"
                        >
                            {{ creating ? 'Создание...' : 'Создать' }}
                        </button>
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 border border-border rounded-lg hover:bg-accent/10 transition-colors"
                        >
                            Отмена
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'
import StatusBadge from '../../components/admin/StatusBadge.vue'

export default {
    name: 'Support',
    components: {
        StatusBadge,
    },
    setup() {
        const router = useRouter()
        const loading = ref(false)
        const error = ref(null)
        const tickets = ref([])
        const pagination = ref(null)
        const showCreateModal = ref(false)
        const creating = ref(false)
        const filters = ref({
            search: '',
            status: ''
        })
        const currentPage = ref(1)
        const perPage = ref(20)
        const searchTimeout = ref(null)
        const newTicket = ref({
            theme: '',
            message: '',
            attachments: []
        })

        const getAuthHeaders = () => {
            const token = localStorage.getItem('token')
            return {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            }
        }

        const fetchTickets = async (page = 1) => {
            loading.value = true
            error.value = null
            try {
                const params = new URLSearchParams()
                params.append('page', page)
                params.append('per_page', perPage.value)
                if (filters.value.status) {
                    params.append('status', filters.value.status)
                }

                const response = await axios.get(`/api/v1/support/tickets?${params.toString()}`, {
                    headers: getAuthHeaders()
                })

                if (response.data.success) {
                    tickets.value = response.data.data.data || []
                    pagination.value = {
                        current_page: response.data.data.current_page,
                        last_page: response.data.data.last_page,
                        from: response.data.data.from,
                        to: response.data.data.to,
                        total: response.data.data.total,
                    }
                }
            } catch (err) {
                error.value = err.response?.data?.message || 'Ошибка при загрузке тикетов'
                console.error('Error fetching tickets:', err)
            } finally {
                loading.value = false
            }
        }


        const handleFilterChange = () => {
            currentPage.value = 1
            fetchTickets(1)
        }

        const handleSearch = () => {
            if (searchTimeout.value) {
                clearTimeout(searchTimeout.value)
            }
            searchTimeout.value = setTimeout(() => {
                currentPage.value = 1
                fetchTickets(1)
            }, 500)
        }

        const handlePageChange = (page) => {
            currentPage.value = page
            fetchTickets(page)
        }

        const getPageNumbers = (current, last) => {
            const pages = []
            const maxPages = 5
            let start = Math.max(1, current - Math.floor(maxPages / 2))
            let end = Math.min(last, start + maxPages - 1)
            if (end - start < maxPages - 1) {
                start = Math.max(1, end - maxPages + 1)
            }
            for (let i = start; i <= end; i++) {
                pages.push(i)
            }
            return pages
        }

        const formatDate = (date) => {
            if (!date) return ''
            return new Date(date).toLocaleString('ru-RU')
        }

        const handleFileSelect = (event) => {
            const files = Array.from(event.target.files)
            newTicket.value.attachments = files
        }

        const removeAttachment = (index) => {
            newTicket.value.attachments.splice(index, 1)
        }

        const createTicket = async () => {
            creating.value = true
            try {
                const formData = new FormData()
                formData.append('theme', newTicket.value.theme)
                formData.append('message', newTicket.value.message)
                newTicket.value.attachments.forEach((file) => {
                    formData.append('attachments[]', file)
                })

                const response = await axios.post('/api/v1/support/ticket', formData, {
                    headers: {
                        ...getAuthHeaders(),
                        'Content-Type': 'multipart/form-data',
                    }
                })

                if (response.data.success) {
                    Swal.fire('Успех', 'Тикет успешно создан', 'success')
                    showCreateModal.value = false
                    newTicket.value = { theme: '', message: '', attachments: [] }
                    fetchTickets()
                } else {
                    Swal.fire('Ошибка', response.data.message || 'Не удалось создать тикет', 'error')
                }
            } catch (err) {
                Swal.fire('Ошибка', err.response?.data?.message || 'Не удалось создать тикет', 'error')
            } finally {
                creating.value = false
            }
        }

        onMounted(() => {
            fetchTickets()
        })

        return {
            router,
            loading,
            error,
            tickets,
            pagination,
            showCreateModal,
            creating,
            filters,
            newTicket,
            fetchTickets,
            handleFilterChange,
            handleSearch,
            handlePageChange,
            getPageNumbers,
            formatDate,
            handleFileSelect,
            removeAttachment,
            createTicket,
        }
    }
}
</script>

