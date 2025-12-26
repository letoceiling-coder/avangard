<template>
    <div
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="$emit('close')"
    >
        <div class="bg-card rounded-lg border border-border w-full max-w-4xl mx-4 max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="p-4 border-b border-border flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold">{{ ticket.subject || ticket.theme }}</h2>
                    <div class="flex items-center gap-2 mt-1">
                        <StatusBadge :status="ticket.status" />
                        <span class="text-xs text-muted-foreground">
                            Создан: {{ formatDate(ticket.created_at) }}
                        </span>
                    </div>
                </div>
                <button
                    @click="$emit('close')"
                    class="p-2 hover:bg-accent/10 rounded transition-colors"
                >
                    ✕
                </button>
            </div>

            <!-- Messages -->
            <div
                ref="messagesContainer"
                class="flex-1 overflow-y-auto p-4 space-y-4"
                :class="{
                    'opacity-50 pointer-events-none': !isChatEnabled
                }"
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
                        <p class="text-sm whitespace-pre-wrap">{{ message.body || message.message || '' }}</p>
                        <div v-if="message.attachments && message.attachments.length > 0" class="mt-2 space-y-2">
                            <div
                                v-for="(attachment, index) in message.attachments"
                                :key="index"
                                class="mt-2"
                            >
                                <a
                                    v-if="attachment.url"
                                    :href="attachment.url"
                                    target="_blank"
                                    class="text-xs underline flex items-center gap-1"
                                >
                                    📎 {{ attachment.name }}
                                </a>
                            </div>
                        </div>
                        <p class="text-xs mt-2 opacity-70">
                            {{ formatDate(message.created_at) }}
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
                            required
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
                        <div v-if="attachments.length > 0" class="mt-2 space-y-1">
                            <div
                                v-for="(file, index) in attachments"
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
                    <div class="flex gap-3">
                        <button
                            type="submit"
                            :disabled="sending || !newMessage.trim()"
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
import { ref, computed, watch, nextTick } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'
import StatusBadge from './StatusBadge.vue'

export default {
    name: 'TicketChat',
    components: {
        StatusBadge,
    },
    props: {
        ticket: {
            type: Object,
            required: true
        }
    },
    emits: ['close', 'refresh'],
    setup(props, { emit }) {
        const newMessage = ref('')
        const attachments = ref([])
        const sending = ref(false)
        const messagesContainer = ref(null)

        const isChatEnabled = computed(() => {
            return ['open', 'in_progress'].includes(props.ticket.status)
        })

        const sortedMessages = computed(() => {
            if (!props.ticket.messages || !Array.isArray(props.ticket.messages)) {
                return []
            }
            return [...props.ticket.messages].sort((a, b) => {
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

        const scrollToBottom = () => {
            nextTick(() => {
                if (messagesContainer.value) {
                    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
                }
            })
        }

        const handleFileSelect = (event) => {
            const files = Array.from(event.target.files)
            attachments.value = files
        }

        const removeAttachment = (index) => {
            attachments.value.splice(index, 1)
        }

        const sendMessage = async () => {
            if (!newMessage.value.trim() && attachments.value.length === 0) {
                return
            }

            sending.value = true
            try {
                const formData = new FormData()
                formData.append('ticket_id', props.ticket.id)
                formData.append('message', newMessage.value)
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
                    newMessage.value = ''
                    attachments.value = []
                    emit('refresh')
                    // Reload ticket to get new message
                    const ticketResponse = await axios.get(`/api/v1/support/tickets/${props.ticket.id}`, {
                        headers: getAuthHeaders()
                    })
                    if (ticketResponse.data.success) {
                        Object.assign(props.ticket, ticketResponse.data.data)
                        scrollToBottom()
                    }
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

        watch(() => props.ticket.messages, () => {
            scrollToBottom()
        }, { deep: true })

        return {
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
        }
    }
}
</script>

