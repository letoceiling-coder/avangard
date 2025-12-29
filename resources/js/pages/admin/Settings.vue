<template>
    <div class="settings-page">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-foreground">Настройки</h1>
            <p class="text-muted-foreground mt-1">Настройки системы</p>
        </div>

        <!-- Настройки TrendAgent -->
        <div class="bg-card rounded-lg border border-border p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-foreground">Настройки TrendAgent API</h2>
            <p class="text-sm text-muted-foreground mb-4">
                Учетные данные для авторизации в TrendAgent API. Используются для парсера и синхронизации данных.
            </p>

            <form @submit.prevent="saveTrendSettings" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-2 text-foreground">
                        Телефон <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="trendSettings.phone"
                        type="text"
                        placeholder="+79045393434"
                        required
                        class="w-full h-10 px-3 border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-ring text-sm"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Телефон в формате +7 999 123 45 67
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2 text-foreground">
                        Пароль <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            v-model="trendSettings.password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="Пароль"
                            required
                            class="w-full h-10 px-3 pr-10 border border-border rounded bg-background focus:outline-none focus:ring-2 focus:ring-ring text-sm"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        >
                            <svg v-if="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <button
                        type="submit"
                        :disabled="saving"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <span v-if="saving">Сохранение...</span>
                        <span v-else>Сохранить настройки</span>
                    </button>
                    <button
                        type="button"
                        @click="loadTrendSettings"
                        :disabled="saving"
                        class="px-4 py-2 border border-border rounded hover:bg-accent disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        Отменить
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { apiGet, apiPut } from '../../utils/api'
import Swal from 'sweetalert2'

export default {
    name: 'Settings',
    setup() {
        const saving = ref(false)
        const showPassword = ref(false)
        const trendSettings = ref({
            phone: '+79045393434',
            password: 'nwBvh4q',
        })

        const loadTrendSettings = async () => {
            try {
                const response = await apiGet('/settings/trend')
                if (!response.ok) {
                    throw new Error('Ошибка загрузки настроек')
                }
                const data = await response.json()
                trendSettings.value = data.data || {
                    phone: '+79045393434',
                    password: 'nwBvh4q',
                }
            } catch (error) {
                console.error('Ошибка загрузки настроек TrendAgent:', error)
                // Используем значения по умолчанию при ошибке
                trendSettings.value = {
                    phone: '+79045393434',
                    password: 'nwBvh4q',
                }
            }
        }

        const saveTrendSettings = async () => {
            saving.value = true
            try {
                const response = await apiPut('/settings/trend', {
                    phone: trendSettings.value.phone,
                    password: trendSettings.value.password,
                })

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}))
                    throw new Error(errorData.message || 'Ошибка сохранения настроек')
                }

                await Swal.fire({
                    icon: 'success',
                    title: 'Настройки сохранены',
                    text: 'Настройки TrendAgent успешно обновлены',
                    timer: 2000,
                    showConfirmButton: false,
                })
            } catch (error) {
                console.error('Ошибка сохранения настроек:', error)
                await Swal.fire({
                    icon: 'error',
                    title: 'Ошибка',
                    text: error.message || 'Не удалось сохранить настройки',
                })
            } finally {
                saving.value = false
            }
        }

        onMounted(() => {
            loadTrendSettings()
        })

        return {
            saving,
            showPassword,
            trendSettings,
            loadTrendSettings,
            saveTrendSettings,
        }
    },
}
</script>
