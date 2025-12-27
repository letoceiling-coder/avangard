<template>
    <div class="bot-settings-form space-y-6">
        <h2 class="text-2xl font-semibold">Настройки бота</h2>

        <!-- Tabs -->
        <div class="border-b border-border">
            <nav class="flex -mb-px">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="[
                        'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
                        activeTab === tab.key
                            ? 'border-accent text-accent'
                            : 'border-transparent text-muted-foreground hover:text-foreground hover:border-muted-foreground'
                    ]"
                >
                    {{ tab.label }}
                </button>
            </nav>
        </div>

        <form @submit.prevent="saveSettings" class="space-y-6">
            <!-- Основные настройки -->
            <div v-if="activeTab === 'main'" class="bg-card rounded-lg border border-border p-6 space-y-4">
                <h3 class="text-lg font-semibold">Основные настройки</h3>

                <div>
                    <label class="block text-sm font-medium mb-2">ID канала</label>
                    <input
                        v-model.number="form.required_channel_id"
                        type="number"
                        placeholder="-1001234567890"
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        ID канала (можно получить через бота @userinfobot)
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Username канала</label>
                    <input
                        v-model="form.required_channel_username"
                        type="text"
                        placeholder="aip_channel"
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Username канала без символа @
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Telegram ID администраторов</label>
                    <div class="space-y-2">
                        <div
                            v-for="(adminId, index) in form.admin_telegram_ids"
                            :key="index"
                            class="flex gap-2"
                        >
                            <input
                                v-model.number="form.admin_telegram_ids[index]"
                                type="number"
                                placeholder="123456789"
                                class="flex-1 h-10 px-3 border border-border rounded-lg bg-background"
                            />
                            <button
                                type="button"
                                @click="removeAdmin(index)"
                                class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg"
                            >
                                Удалить
                            </button>
                        </div>
                        <button
                            type="button"
                            @click="addAdmin"
                            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg"
                        >
                            + Добавить администратора
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Ссылка на Яндекс Карты</label>
                    <input
                        v-model="form.yandex_maps_url"
                        type="url"
                        placeholder="https://yandex.ru/maps/org/..."
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Приветственное сообщение</label>
                    <textarea
                        v-model="form.welcome_message"
                        rows="6"
                        placeholder="Добро пожаловать..."
                        class="w-full px-3 py-2 border border-border rounded-lg bg-background resize-none"
                    ></textarea>
                </div>
            </div>

            <!-- Тексты сообщений -->
            <div v-if="activeTab === 'messages'" class="bg-card rounded-lg border border-border p-6 space-y-6">
                <h3 class="text-lg font-semibold">Тексты сообщений бота</h3>

                <!-- Подписка на канал -->
                <div class="space-y-4">
                    <h4 class="text-md font-medium">Подписка на канал</h4>
                    <div>
                        <label class="block text-sm font-medium mb-2">Текст экрана подписки</label>
                        <textarea
                            v-model="form.messages.subscription.required_text"
                            rows="3"
                            placeholder="Для доступа к бета-версии необходимо подписаться..."
                            class="w-full px-3 py-2 border border-border rounded-lg bg-background resize-none"
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Текст кнопки подписки</label>
                        <input
                            v-model="form.messages.subscription.subscribe_button"
                            type="text"
                            placeholder="🔔 Подписаться на Telegram"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Текст кнопки проверки</label>
                        <input
                            v-model="form.messages.subscription.check_button"
                            type="text"
                            placeholder="✅ Я подписался"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                        />
                    </div>
                </div>

                <!-- Консультация -->
                <div class="space-y-4 pt-4 border-t border-border">
                    <h4 class="text-md font-medium">Консультация</h4>
                    <div>
                        <label class="block text-sm font-medium mb-2">Описание услуги</label>
                        <textarea
                            v-model="form.messages.consultation.description"
                            rows="4"
                            placeholder="Если вашему бизнесу нужна профессиональная..."
                            class="w-full px-3 py-2 border border-border rounded-lg bg-background resize-none"
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Текст поля "Имя"</label>
                        <input
                            v-model="form.messages.consultation.form_name_label"
                            type="text"
                            placeholder="Введите ваше имя:"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Текст поля "Телефон"</label>
                        <input
                            v-model="form.messages.consultation.form_phone_label"
                            type="text"
                            placeholder="Введите ваш телефон:"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Текст поля "Описание"</label>
                        <input
                            v-model="form.messages.consultation.form_description_label"
                            type="text"
                            placeholder="Краткое описание запроса (опционально...):"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Сообщение после отправки</label>
                        <textarea
                            v-model="form.messages.consultation.thank_you"
                            rows="2"
                            placeholder="Спасибо. Мы свяжемся с вами в ближайшее время."
                            class="w-full px-3 py-2 border border-border rounded-lg bg-background resize-none"
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Текст кнопки "Записаться"</label>
                        <input
                            v-model="form.messages.consultation.start_button"
                            type="text"
                            placeholder="📝 Записаться на консультацию"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Текст кнопки "Пропустить"</label>
                        <input
                            v-model="form.messages.consultation.skip_description_button"
                            type="text"
                            placeholder="Пропустить"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                        />
                    </div>
                </div>

                <!-- Материалы -->
                <div class="space-y-4 pt-4 border-t border-border">
                    <h4 class="text-md font-medium">Материалы</h4>
                    <div>
                        <label class="block text-sm font-medium mb-2">Описание списка материалов</label>
                        <textarea
                            v-model="form.messages.materials.list_description"
                            rows="3"
                            placeholder="Мы подготовили материалы по ключевым направлениям..."
                            class="w-full px-3 py-2 border border-border rounded-lg bg-background resize-none"
                        ></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Текст кнопки скачивания</label>
                        <input
                            v-model="form.messages.materials.download_button"
                            type="text"
                            placeholder="⬇️ Скачать материалы"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Текст кнопки "Назад"</label>
                        <input
                            v-model="form.messages.materials.back_to_list"
                            type="text"
                            placeholder="⬅️ Назад"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                        />
                    </div>
                </div>

                <!-- Главное меню -->
                <div class="space-y-4 pt-4 border-t border-border">
                    <h4 class="text-md font-medium">Главное меню</h4>
                    <div>
                        <label class="block text-sm font-medium mb-2">Текст кнопки "Материалы"</label>
                        <input
                            v-model="form.messages.menu.materials_button"
                            type="text"
                            placeholder="📂 Полезные материалы и договоры"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Текст кнопки "Консультация"</label>
                        <input
                            v-model="form.messages.menu.consultation_button"
                            type="text"
                            placeholder="📞 Записаться на консультацию"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Текст кнопки "Отзыв"</label>
                        <input
                            v-model="form.messages.menu.review_button"
                            type="text"
                            placeholder="⭐ Оставить отзыв на Яндекс Картах"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Текст кнопки "Назад в меню"</label>
                        <input
                            v-model="form.messages.menu.back_to_menu"
                            type="text"
                            placeholder="⬅️ Назад в меню"
                            class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                        />
                    </div>
                </div>

                <!-- Уведомления -->
                <div class="space-y-4 pt-4 border-t border-border">
                    <h4 class="text-md font-medium">Уведомления администраторам</h4>
                    <div>
                        <label class="block text-sm font-medium mb-2">Шаблон уведомления о новой заявке</label>
                        <textarea
                            v-model="form.messages.notifications.consultation_template"
                            rows="6"
                            placeholder="Новая заявка на консультацию&#10;&#10;Имя: {name}&#10;Телефон: {phone}&#10;Описание: {description}&#10;Дата: {date}"
                            class="w-full px-3 py-2 border border-border rounded-lg bg-background resize-none font-mono text-sm"
                        ></textarea>
                        <p class="text-xs text-muted-foreground mt-1">
                            Используйте переменные: {name}, {phone}, {description}, {date}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Дополнительные настройки -->
            <div v-if="activeTab === 'advanced'" class="bg-card rounded-lg border border-border p-6 space-y-4">
                <h3 class="text-lg font-semibold">Дополнительные настройки</h3>

                <div>
                    <label class="flex items-center gap-2">
                        <input
                            v-model="form.other_settings.phone_validation_strict"
                            type="checkbox"
                            class="w-4 h-4"
                        />
                        <span>Строгая валидация телефона</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Максимальная длина описания</label>
                    <input
                        v-model.number="form.other_settings.max_description_length"
                        type="number"
                        min="10"
                        max="5000"
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Таймаут проверки подписки (сек)</label>
                    <input
                        v-model.number="form.other_settings.subscription_check_timeout"
                        type="number"
                        min="1"
                        max="30"
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                    />
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-4">
                <button
                    type="submit"
                    :disabled="saving"
                    class="flex-1 h-11 px-6 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-2xl disabled:opacity-50"
                >
                    {{ saving ? 'Сохранение...' : 'Сохранить настройки' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { apiGet, apiPut } from '../../utils/api'
import Swal from 'sweetalert2'

export default {
    name: 'BotSettingsForm',
    props: {
        botId: {
            type: [String, Number],
            required: true,
        },
    },
    emits: ['updated'],
    setup(props, { emit }) {
        const loading = ref(false)
        const saving = ref(false)
        const form = ref({
            required_channel_id: null,
            required_channel_username: '',
            admin_telegram_ids: [],
            yandex_maps_url: '',
            welcome_message: '',
            other_settings: {
                phone_validation_strict: false,
                max_description_length: 1000,
                subscription_check_timeout: 5,
            },
        })

        const fetchSettings = async () => {
            loading.value = true
            try {
                const response = await apiGet(`/bot-management/${props.botId}/settings`)
                if (!response.ok) {
                    throw new Error('Ошибка загрузки настроек')
                }

                const data = await response.json()
                if (data.success && data.data) {
                    const settings = data.data.settings || {}
                    const messages = settings.messages || {}

                    form.value = {
                        required_channel_id: data.data.required_channel_id || null,
                        required_channel_username: data.data.required_channel_username || '',
                        admin_telegram_ids: data.data.admin_telegram_ids || [],
                        yandex_maps_url: data.data.yandex_maps_url || '',
                        welcome_message: data.data.welcome_message || '',
                        messages: {
                            subscription: messages.subscription || {
                                required_text: '',
                                subscribe_button: '',
                                check_button: '',
                            },
                            consultation: messages.consultation || {
                                description: '',
                                form_name_label: '',
                                form_phone_label: '',
                                form_description_label: '',
                                thank_you: '',
                                start_button: '',
                                skip_description_button: '',
                            },
                            materials: messages.materials || {
                                list_description: '',
                                download_button: '',
                                back_to_list: '',
                            },
                            menu: messages.menu || {
                                materials_button: '',
                                consultation_button: '',
                                review_button: '',
                                back_to_menu: '',
                            },
                            notifications: messages.notifications || {
                                consultation_template: '',
                            },
                        },
                        other_settings: settings.other_settings || {
                            phone_validation_strict: false,
                            max_description_length: 1000,
                            subscription_check_timeout: 5,
                        },
                    }
                }
            } catch (err) {
                console.error('Error fetching settings:', err)
            } finally {
                loading.value = false
            }
        }

        const saveSettings = async () => {
            saving.value = true
            try {
                const response = await apiPut(`/bot-management/${props.botId}/settings`, {
                    required_channel_id: form.value.required_channel_id,
                    required_channel_username: form.value.required_channel_username,
                    admin_telegram_ids: form.value.admin_telegram_ids,
                    yandex_maps_url: form.value.yandex_maps_url,
                    welcome_message: form.value.welcome_message,
                    settings: {
                        messages: form.value.messages,
                        other_settings: form.value.other_settings,
                    },
                })

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}))
                    throw new Error(errorData.message || 'Ошибка сохранения настроек')
                }

                await Swal.fire({
                    title: 'Сохранено',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                })

                emit('updated')
            } catch (err) {
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка сохранения настроек',
                    icon: 'error',
                    confirmButtonText: 'ОК',
                })
            } finally {
                saving.value = false
            }
        }

        const addAdmin = () => {
            form.value.admin_telegram_ids.push(null)
        }

        const removeAdmin = (index) => {
            form.value.admin_telegram_ids.splice(index, 1)
        }

        onMounted(() => {
            fetchSettings()
        })

        return {
            loading,
            saving,
            activeTab,
            tabs,
            form,
            fetchSettings,
            saveSettings,
            addAdmin,
            removeAdmin,
        }
    },
}
</script>

