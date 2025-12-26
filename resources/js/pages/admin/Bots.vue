<template>
    <div class="bots-page space-y-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-semibold text-foreground">Боты</h1>
                <p class="text-muted-foreground mt-1">Управление Telegram ботами</p>
            </div>
            <button
                @click="showCreateModal = true"
                class="h-11 px-6 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-2xl shadow-lg shadow-accent/10 inline-flex items-center justify-center gap-2"
            >
                <span>+</span>
                <span>Добавить бота</span>
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-12">
            <p class="text-muted-foreground">Загрузка ботов...</p>
        </div>

        <!-- Error State -->
        <div v-if="error" class="p-4 bg-destructive/10 border border-destructive/20 rounded-lg">
            <p class="text-destructive">{{ error }}</p>
        </div>

        <!-- Bots List -->
        <div v-if="!loading && bots.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="bot in bots"
                :key="bot.id"
                class="bg-card rounded-lg border border-border p-6 hover:shadow-lg transition-shadow"
            >
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-foreground">{{ bot.name }}</h3>
                        <p v-if="bot.username" class="text-sm text-muted-foreground">@{{ bot.username }}</p>
                    </div>
                    <span
                        :class="[
                            'px-2 py-1 text-xs rounded-md',
                            bot.is_active
                                ? 'bg-green-500/10 text-green-500'
                                : 'bg-gray-500/10 text-gray-500'
                        ]"
                    >
                        {{ bot.is_active ? 'Активен' : 'Неактивен' }}
                    </span>
                </div>

                <div class="space-y-2 mb-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Webhook:</span>
                        <span
                            :class="[
                                'px-2 py-1 text-xs rounded-md',
                                bot.webhook_registered
                                    ? 'bg-green-500/10 text-green-500'
                                    : 'bg-red-500/10 text-red-500'
                            ]"
                        >
                            {{ bot.webhook_registered ? 'Установлен' : 'Не установлен' }}
                        </span>
                    </div>
                    <div v-if="bot.webhook_url" class="text-xs text-muted-foreground truncate" :title="bot.webhook_url">
                        {{ bot.webhook_url }}
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        @click="checkWebhook(bot)"
                        :disabled="checkingWebhook === bot.id"
                        class="flex-1 px-3 py-2 text-xs bg-blue-500/10 hover:bg-blue-500/20 text-blue-500 rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ checkingWebhook === bot.id ? 'Проверка...' : 'Проверить webhook' }}
                    </button>
                    <button
                        @click="registerWebhook(bot)"
                        :disabled="registeringWebhook === bot.id"
                        class="flex-1 px-3 py-2 text-xs bg-green-500/10 hover:bg-green-500/20 text-green-500 rounded-lg transition-colors disabled:opacity-50"
                    >
                        {{ registeringWebhook === bot.id ? 'Регистрация...' : 'Установить webhook' }}
                    </button>
                    <button
                        @click="editBot(bot)"
                        class="px-3 py-2 text-xs bg-yellow-500/10 hover:bg-yellow-500/20 text-yellow-500 rounded-lg transition-colors"
                    >
                        Редактировать
                    </button>
                    <button
                        @click="deleteBot(bot)"
                        class="px-3 py-2 text-xs bg-red-500/10 hover:bg-red-500/20 text-red-500 rounded-lg transition-colors"
                    >
                        Удалить
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && bots.length === 0" class="bg-card rounded-lg border border-border p-12 text-center">
            <p class="text-muted-foreground">Боты не найдены. Добавьте первого бота.</p>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-background border-b border-border p-6">
                    <h3 class="text-lg font-semibold">
                        {{ showEditModal ? 'Редактировать бота' : 'Добавить бота' }}
                    </h3>
                </div>
                <form @submit.prevent="saveBot" class="p-6 space-y-4">
                    <div>
                        <label class="text-sm font-medium mb-1 block">Название бота *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="Мой бот"
                            class="w-full h-10 px-3 border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Токен бота *</label>
                        <input
                            v-model="form.token"
                            type="text"
                            required
                            placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz"
                            class="w-full h-10 px-3 border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-ring font-mono text-sm"
                        />
                        <p class="text-xs text-muted-foreground mt-1">Получить токен можно у @BotFather</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Приветственное сообщение</label>
                        <textarea
                            v-model="form.welcome_message"
                            rows="4"
                            placeholder="Добро пожаловать! Это приветственное сообщение..."
                            class="w-full px-3 py-2 border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                        ></textarea>
                    </div>
                    <div>
                        <label class="text-sm font-medium mb-1 block">Активен</label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="w-4 h-4"
                            />
                            <span class="text-sm">Бот активен</span>
                        </label>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium mb-2 block">Настройки Webhook</label>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs text-muted-foreground mb-1 block">Разрешенные обновления (через запятую)</label>
                                    <input
                                        v-model="form.webhook_allowed_updates"
                                        type="text"
                                        placeholder="message, callback_query, inline_query"
                                        class="w-full h-10 px-3 border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-ring text-sm"
                                    />
                                    <p class="text-xs text-muted-foreground mt-1">Доступные: message, edited_message, channel_post, edited_channel_post, callback_query, inline_query, chosen_inline_result, shipping_query, pre_checkout_query, poll, poll_answer, my_chat_member, chat_member, chat_join_request</p>
                                </div>
                                <div>
                                    <label class="text-xs text-muted-foreground mb-1 block">Максимальное количество соединений (1-100)</label>
                                    <input
                                        v-model.number="form.webhook_max_connections"
                                        type="number"
                                        min="1"
                                        max="100"
                                        placeholder="40"
                                        class="w-full h-10 px-3 border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-ring text-sm"
                                    />
                                </div>
                                <div>
                                    <label class="text-xs text-muted-foreground mb-1 block">Secret Token (опционально)</label>
                                    <input
                                        v-model="form.webhook_secret_token"
                                        type="text"
                                        placeholder="Секретный токен для проверки webhook"
                                        class="w-full h-10 px-3 border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-ring text-sm font-mono"
                                    />
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium mb-1 block">Дополнительные настройки (JSON)</label>
                            <textarea
                                v-model="settingsJson"
                                rows="6"
                                placeholder='{"key": "value"}'
                                class="w-full px-3 py-2 border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-ring resize-none font-mono text-sm"
                            ></textarea>
                            <p class="text-xs text-muted-foreground mt-1">Дополнительные настройки бота в формате JSON</p>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-4 border-t border-border">
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

        <!-- Webhook Info Modal -->
        <div v-if="webhookInfo" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-md">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Информация о webhook</h3>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm font-medium text-muted-foreground">URL:</span>
                            <p class="text-sm text-foreground break-all">{{ webhookInfo.url || 'Не установлен' }}</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-muted-foreground">Ожидающие обновления:</span>
                            <p class="text-sm text-foreground">{{ webhookInfo.pending_update_count || 0 }}</p>
                        </div>
                        <div v-if="webhookInfo.last_error_message">
                            <span class="text-sm font-medium text-muted-foreground">Последняя ошибка:</span>
                            <p class="text-sm text-red-500">{{ webhookInfo.last_error_message }}</p>
                        </div>
                        <div v-if="webhookInfo.max_connections">
                            <span class="text-sm font-medium text-muted-foreground">Макс. соединений:</span>
                            <p class="text-sm text-foreground">{{ webhookInfo.max_connections }}</p>
                        </div>
                    </div>
                    <button
                        @click="webhookInfo = null"
                        class="w-full mt-4 h-10 px-4 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors"
                    >
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { apiGet, apiPost, apiPut, apiDelete } from '../../utils/api'
import Swal from 'sweetalert2'

export default {
    name: 'Bots',
    setup() {
        const loading = ref(false)
        const saving = ref(false)
        const error = ref(null)
        const bots = ref([])
        const showCreateModal = ref(false)
        const showEditModal = ref(false)
        const checkingWebhook = ref(null)
        const registeringWebhook = ref(null)
        const webhookInfo = ref(null)
        
        const form = ref({
            id: null,
            name: '',
            token: '',
            welcome_message: '',
            is_active: true,
            settings: {},
            webhook_allowed_updates: 'message, callback_query',
            webhook_max_connections: 40,
            webhook_secret_token: '',
        })

        const settingsJson = computed({
            get: () => {
                return JSON.stringify(form.value.settings || {}, null, 2)
            },
            set: (value) => {
                try {
                    form.value.settings = JSON.parse(value || '{}')
                } catch (e) {
                    // Оставляем старое значение при ошибке парсинга
                }
            }
        })

        const fetchBots = async () => {
            loading.value = true
            error.value = null
            try {
                const response = await apiGet('/v1/bots')
                if (!response.ok) {
                    throw new Error('Ошибка загрузки ботов')
                }
                const data = await response.json()
                bots.value = data.data || []
            } catch (err) {
                error.value = err.message || 'Ошибка загрузки ботов'
            } finally {
                loading.value = false
            }
        }

        const editBot = (bot) => {
            const settings = bot.settings || {}
            const webhookSettings = settings.webhook || {}
            
            form.value = {
                id: bot.id,
                name: bot.name,
                token: bot.token,
                welcome_message: bot.welcome_message || '',
                is_active: bot.is_active ?? true,
                settings: settings,
                webhook_allowed_updates: Array.isArray(webhookSettings.allowed_updates) 
                    ? webhookSettings.allowed_updates.join(', ') 
                    : (webhookSettings.allowed_updates || 'message, callback_query'),
                webhook_max_connections: webhookSettings.max_connections || 40,
                webhook_secret_token: webhookSettings.secret_token || '',
            }
            showEditModal.value = true
        }

        const deleteBot = async (bot) => {
            const result = await Swal.fire({
                title: 'Удалить бота?',
                html: `Вы уверены, что хотите удалить бота <strong>"${bot.name}"</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Да, удалить',
                cancelButtonText: 'Отмена',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
            })

            if (!result.isConfirmed) return

            try {
                const response = await apiDelete(`/v1/bots/${bot.id}`)
                if (!response.ok) {
                    const errorData = await response.json()
                    throw new Error(errorData.message || 'Ошибка удаления бота')
                }
                await Swal.fire({
                    title: 'Бот удален',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                })
                await fetchBots()
            } catch (err) {
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка удаления бота',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
            }
        }

        const saveBot = async () => {
            saving.value = true
            error.value = null
            try {
                const botData = {
                    name: form.value.name,
                    token: form.value.token,
                    welcome_message: form.value.welcome_message,
                    is_active: form.value.is_active,
                }

                // Формируем настройки webhook
                const allowedUpdates = form.value.webhook_allowed_updates
                    .split(',')
                    .map(u => u.trim())
                    .filter(u => u)
                
                botData.webhook = {
                    allowed_updates: allowedUpdates,
                    max_connections: form.value.webhook_max_connections || 40,
                }
                
                if (form.value.webhook_secret_token) {
                    botData.webhook.secret_token = form.value.webhook_secret_token
                }

                if (showEditModal.value) {
                    const settings = {
                        ...form.value.settings,
                        webhook: botData.webhook
                    }
                    botData.settings = settings
                }

                let response
                if (showEditModal.value) {
                    response = await apiPut(`/v1/bots/${form.value.id}`, botData)
                } else {
                    response = await apiPost('/v1/bots', botData)
                }

                if (!response.ok) {
                    const errorData = await response.json()
                    throw new Error(errorData.message || 'Ошибка сохранения бота')
                }

                await Swal.fire({
                    title: showEditModal.value ? 'Бот обновлен' : 'Бот создан',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                })

                closeModal()
                await fetchBots()
            } catch (err) {
                error.value = err.message || 'Ошибка сохранения бота'
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка сохранения бота',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
            } finally {
                saving.value = false
            }
        }

        const checkWebhook = async (bot) => {
            checkingWebhook.value = bot.id
            try {
                const response = await apiGet(`/v1/bots/${bot.id}/check-webhook`)
                if (!response.ok) {
                    const errorData = await response.json()
                    throw new Error(errorData.message || 'Ошибка проверки webhook')
                }
                const data = await response.json()
                webhookInfo.value = data.data?.data || data.data || {}
            } catch (err) {
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка проверки webhook',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
            } finally {
                checkingWebhook.value = null
            }
        }

        const registerWebhook = async (bot) => {
            registeringWebhook.value = bot.id
            try {
                const response = await apiPost(`/v1/bots/${bot.id}/register-webhook`)
                if (!response.ok) {
                    const errorData = await response.json()
                    throw new Error(errorData.message || 'Ошибка регистрации webhook')
                }
                const data = await response.json()
                
                await Swal.fire({
                    title: data.success ? 'Webhook установлен' : 'Ошибка',
                    text: data.message || (data.success ? 'Webhook успешно установлен' : 'Не удалось установить webhook'),
                    icon: data.success ? 'success' : 'error',
                    confirmButtonText: 'ОК'
                })
                
                await fetchBots()
            } catch (err) {
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка регистрации webhook',
                    icon: 'error',
                    confirmButtonText: 'ОК'
                })
            } finally {
                registeringWebhook.value = null
            }
        }

        const closeModal = () => {
            showCreateModal.value = false
            showEditModal.value = false
            form.value = {
                id: null,
                name: '',
                token: '',
                welcome_message: '',
                is_active: true,
                settings: {},
                webhook_allowed_updates: 'message, callback_query',
                webhook_max_connections: 40,
                webhook_secret_token: '',
            }
        }

        onMounted(async () => {
            await fetchBots()
        })

        return {
            loading,
            saving,
            error,
            bots,
            showCreateModal,
            showEditModal,
            form,
            settingsJson,
            checkingWebhook,
            registeringWebhook,
            webhookInfo,
            editBot,
            deleteBot,
            saveBot,
            checkWebhook,
            registerWebhook,
            closeModal,
        }
    }
}
</script>

