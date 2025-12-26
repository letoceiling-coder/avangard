<template>
    <div class="support-ticket-page">
        <div class="mb-4">
            <button
                @click="$router.push({ name: 'admin.support' })"
                class="flex items-center gap-2 text-muted-foreground hover:text-foreground transition-colors"
            >
                ← Назад к списку тикетов
            </button>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка тикета...</p>
        </div>

        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg mb-4">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <div v-if="ticket && !loading" class="bg-card rounded-lg border border-border">
            <!-- Header -->
            <div class="p-4 border-b border-border">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h1 class="text-2xl font-semibold mb-2">{{ ticket.subject || ticket.theme }}</h1>
                        <div class="flex items-center gap-3">
                            <StatusBadge :status="ticket.status" />
                            <span class="text-sm text-muted-foreground">
                                Создан: {{ formatDate(ticket.created_at) }}
                            </span>
                            <span v-if="ticket.messages?.length" class="text-sm text-muted-foreground">
                                Сообщений: {{ ticket.messages.length }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div
                ref="messagesContainer"
                class="p-4 space-y-4 min-h-[400px] max-h-[600px] overflow-y-auto"
            >
                <div
                    v-for="message in sortedMessages"
                    :key="message.id"
                    :class="[
                        'flex',
                        message.sender === 'tma' ? 'justify-end' : 'justify-start'
                    ]"
                >
                    <div
                        :class="[
                            'max-w-[70%] rounded-lg p-3',
                            message.sender === 'tma'
                                ? 'bg-accent text-accent-foreground'
                                : 'bg-muted text-muted-foreground'
                        ]"
                    >
                        <p class="text-sm whitespace-pre-wrap mb-2">{{ message.body || message.message || '' }}</p>
                        <div v-if="message.attachments && message.attachments.length > 0" class="mt-2 space-y-2">
                            <div
                                v-for="(attachment, index) in message.attachments"
                                :key="index"
                                class="flex items-start gap-2"
                            >
                                <!-- Превью изображения -->
                                <a
                                    v-if="attachment.url && isImageAttachment(attachment)"
                                    :href="attachment.url"
                                    target="_blank"
                                    class="flex-shrink-0 hover:opacity-80 transition-opacity"
                                >
                                    <img
                                        :src="attachment.url"
                                        :alt="attachment.name"
                                        class="w-20 h-20 object-cover rounded border border-border/50"
                                    />
                                </a>
                                <!-- Иконка документа -->
                                <div
                                    v-else-if="attachment.url"
                                    class="flex-shrink-0"
                                >
                                    <a
                                        :href="attachment.url"
                                        target="_blank"
                                        class="block w-16 h-16 flex items-center justify-center bg-muted/50 rounded border border-border/50 hover:bg-muted transition-colors"
                                    >
                                        <span class="text-2xl">{{ getAttachmentIcon(attachment) }}</span>
                                    </a>
                                </div>
                                <!-- Информация о файле -->
                                <div class="flex-1 min-w-0">
                                    <a
                                        v-if="attachment.url"
                                        :href="attachment.url"
                                        target="_blank"
                                        class="text-xs underline flex items-center gap-1 hover:opacity-80 block truncate"
                                    >
                                        {{ attachment.name }}
                                    </a>
                                    <span v-else class="text-xs text-muted-foreground truncate block">
                                        {{ attachment.name }}
                                    </span>
                                    <span v-if="attachment.size" class="text-xs text-muted-foreground block">
                                        {{ formatBytes(attachment.size) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs mt-2 opacity-70">
                            {{ message.sender === 'tma' ? 'Вы' : 'CRM' }} • {{ formatDate(message.created_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Chat Disabled Notice -->
            <div
                v-if="!isChatEnabled"
                class="p-4 bg-yellow-500/10 border-t border-yellow-500/20 text-center text-sm text-yellow-600"
            >
                Чат недоступен для закрытых тикетов
            </div>

            <!-- Input -->
            <div
                v-if="isChatEnabled"
                class="p-4 border-t border-border"
            >
                <form @submit.prevent="sendMessage" class="space-y-3">
                    <div>
                        <textarea
                            v-model="newMessage"
                            rows="3"
                            placeholder="Введите сообщение..."
                            class="w-full px-3 py-2 border border-border rounded bg-background resize-none"
                        ></textarea>
                    </div>
                    <div>
                        <input
                            type="file"
                            @change="handleFileSelect"
                            multiple
                            accept="image/*,.pdf,.doc,.docx,.txt"
                            class="w-full h-10 px-3 border border-border rounded bg-background text-sm"
                        />
                        <div v-if="attachments.length > 0" class="mt-2 space-y-2">
                            <div
                                v-for="(file, index) in attachments"
                                :key="index"
                                class="flex items-start gap-3 p-2 border border-border rounded bg-background"
                            >
                                <!-- Превью изображения -->
                                <div v-if="isImageFile(file)" class="flex-shrink-0">
                                    <img
                                        :src="getFilePreview(file)"
                                        :alt="file.name"
                                        class="w-16 h-16 object-cover rounded border border-border"
                                    />
                                </div>
                                <!-- Иконка документа -->
                                <div v-else class="flex-shrink-0">
                                    <div class="w-16 h-16 flex items-center justify-center bg-muted rounded border border-border">
                                        <span class="text-2xl">{{ getFileIcon(file) }}</span>
                                    </div>
                                </div>
                                <!-- Информация о файле -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate">{{ file.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatBytes(file.size) }}</p>
                                </div>
                                <!-- Кнопка удаления -->
                                <button
                                    type="button"
                                    @click="removeAttachment(index)"
                                    class="flex-shrink-0 p-1 text-destructive hover:text-destructive/80 hover:bg-destructive/10 rounded transition-colors"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button
                            type="submit"
                            :disabled="sending || (!newMessage.trim() && attachments.length === 0)"
                            class="px-4 py-2 bg-accent text-accent-foreground rounded-lg hover:bg-accent/90 disabled:opacity-50 transition-colors"
                        >
                            {{ sending ? 'Отправка...' : 'Отправить' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'
import StatusBadge from '../../components/admin/StatusBadge.vue'

export default {
    name: 'SupportTicket',
    components: {
        StatusBadge,
    },
    setup() {
        const route = useRoute()
        const router = useRouter()
        const loading = ref(false)
        const error = ref(null)
        const ticket = ref(null)
        const newMessage = ref('')
        const attachments = ref([])
        const sending = ref(false)
        const messagesContainer = ref(null)

        const isChatEnabled = computed(() => {
            return ticket.value && ['open', 'in_progress'].includes(ticket.value.status)
        })

        const sortedMessages = computed(() => {
            if (!ticket.value?.messages || !Array.isArray(ticket.value.messages)) {
                return []
            }
            return [...ticket.value.messages].sort((a, b) => {
                const dateA = new Date(a.created_at || 0)
                const dateB = new Date(b.created_at || 0)
                return dateA - dateB
            })
        })

        const getAuthHeaders = () => {
            const token = localStorage.getItem('token')
            return {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
            }
        }

        const fetchTicket = async () => {
            const ticketId = route.params.id
            if (!ticketId) {
                error.value = 'ID тикета не указан'
                return
            }

            loading.value = true
            error.value = null
            try {
                const response = await axios.get(`/api/v1/support/tickets/${ticketId}`, {
                    headers: getAuthHeaders()
                })

                if (response.data.success) {
                    ticket.value = response.data.data
                    scrollToBottom()
                } else {
                    error.value = response.data.message || 'Не удалось загрузить тикет'
                }
            } catch (err) {
                error.value = err.response?.data?.message || 'Ошибка при загрузке тикета'
                console.error('Error fetching ticket:', err)
            } finally {
                loading.value = false
            }
        }

        const scrollToBottom = () => {
            nextTick(() => {
                if (messagesContainer.value) {
                    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
                }
            })
        }

        const handleFileSelect = (event) => {
            const files = Array.from(event.target.files)
            // Создаем превью для изображений
            attachments.value = files.map(file => {
                if (isImageFile(file)) {
                    file.previewUrl = URL.createObjectURL(file)
                }
                return file
            })
        }

        const removeAttachment = (index) => {
            const file = attachments.value[index]
            // Очищаем URL превью для изображений, чтобы избежать утечек памяти
            if (file && isImageFile(file) && file.previewUrl) {
                URL.revokeObjectURL(file.previewUrl)
            }
            attachments.value.splice(index, 1)
        }

        const sendMessage = async () => {
            if ((!newMessage.value.trim() && attachments.value.length === 0) || !isChatEnabled.value) {
                return
            }

            sending.value = true
            try {
                const formData = new FormData()
                formData.append('ticket_id', ticket.value.id)
                if (newMessage.value.trim()) {
                    formData.append('message', newMessage.value)
                }
                attachments.value.forEach((file) => {
                    formData.append('attachments[]', file)
                })

                const response = await axios.post('/api/v1/support/message', formData, {
                    headers: {
                        ...getAuthHeaders(),
                        'Content-Type': 'multipart/form-data',
                    }
                })

                if (response.data.success) {
                    // Очищаем превью перед очисткой массива
                    attachments.value.forEach(file => {
                        if (file && file.previewUrl) {
                            URL.revokeObjectURL(file.previewUrl)
                        }
                    })
                    newMessage.value = ''
                    attachments.value = []
                    await fetchTicket()
                    // Попап успеха убран - сообщение просто добавляется в чат
                } else {
                    Swal.fire('Ошибка', response.data.message || 'Не удалось отправить сообщение', 'error')
                }
            } catch (err) {
                Swal.fire('Ошибка', err.response?.data?.message || 'Не удалось отправить сообщение', 'error')
            } finally {
                sending.value = false
            }
        }

        const formatDate = (date) => {
            if (!date) return ''
            return new Date(date).toLocaleString('ru-RU')
        }

        const formatBytes = (bytes, decimals = 2) => {
            if (bytes === 0) return '0 Bytes'
            const k = 1024
            const dm = decimals < 0 ? 0 : decimals
            const sizes = ['Bytes', 'KB', 'MB', 'GB']
            const i = Math.floor(Math.log(bytes) / Math.log(k))
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]
        }

        const isImageFile = (file) => {
            return file.type && file.type.startsWith('image/')
        }

        const isImageAttachment = (attachment) => {
            const name = attachment.name?.toLowerCase() || ''
            const mimeType = attachment.mime_type?.toLowerCase() || ''
            const url = attachment.url?.toLowerCase() || ''
            
            const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp', '.svg']
            const isImageExt = imageExtensions.some(ext => name.endsWith(ext) || url.endsWith(ext))
            const isImageMime = mimeType.startsWith('image/')
            
            return isImageExt || isImageMime
        }

        const getFilePreview = (file) => {
            if (file && isImageFile(file)) {
                return file.previewUrl || URL.createObjectURL(file)
            }
            return ''
        }

        const getFileIcon = (file) => {
            const name = file.name?.toLowerCase() || ''
            const type = file.type?.toLowerCase() || ''
            
            if (type.includes('pdf') || name.endsWith('.pdf')) return '📄'
            if (type.includes('word') || name.endsWith('.doc') || name.endsWith('.docx')) return '📝'
            if (type.includes('excel') || name.endsWith('.xls') || name.endsWith('.xlsx')) return '📊'
            if (type.includes('powerpoint') || name.endsWith('.ppt') || name.endsWith('.pptx')) return '📽️'
            if (name.endsWith('.txt')) return '📃'
            if (name.endsWith('.zip') || name.endsWith('.rar') || name.endsWith('.7z')) return '📦'
            return '📎'
        }

        const getAttachmentIcon = (attachment) => {
            const name = attachment.name?.toLowerCase() || ''
            const mimeType = attachment.mime_type?.toLowerCase() || ''
            
            if (mimeType.includes('pdf') || name.endsWith('.pdf')) return '📄'
            if (mimeType.includes('word') || name.endsWith('.doc') || name.endsWith('.docx')) return '📝'
            if (mimeType.includes('excel') || name.endsWith('.xls') || name.endsWith('.xlsx')) return '📊'
            if (mimeType.includes('powerpoint') || name.endsWith('.ppt') || name.endsWith('.pptx')) return '📽️'
            if (name.endsWith('.txt')) return '📃'
            if (name.endsWith('.zip') || name.endsWith('.rar') || name.endsWith('.7z')) return '📦'
            return '📎'
        }

        watch(() => route.params.id, () => {
            fetchTicket()
        })

        onMounted(() => {
            fetchTicket()
        })

        onBeforeUnmount(() => {
            // Очищаем все превью при размонтировании компонента
            attachments.value.forEach(file => {
                if (file && file.previewUrl) {
                    URL.revokeObjectURL(file.previewUrl)
                }
            })
        })

        return {
            router,
            loading,
            error,
            ticket,
            newMessage,
            attachments,
            sending,
            messagesContainer,
            isChatEnabled,
            sortedMessages,
            handleFileSelect,
            removeAttachment,
            sendMessage,
            formatDate,
            formatBytes,
            isImageFile,
            isImageAttachment,
            getFilePreview,
            getFileIcon,
            getAttachmentIcon,
        }
    }
}
</script>

