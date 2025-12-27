<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-background border-b border-border p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">
                        {{ isEditMode ? 'Редактировать материал' : 'Создать материал' }}
                    </h3>
                    <button @click="$emit('close')" class="text-muted-foreground hover:text-foreground">
                        ✕
                    </button>
                </div>
            </div>

            <form @submit.prevent="saveMaterial" class="p-6 space-y-6">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium mb-2">Название *</label>
                    <input
                        v-model="form.title"
                        type="text"
                        required
                        placeholder="Название материала"
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                    />
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium mb-2">Категория *</label>
                    <select
                        v-model.number="form.category_id"
                        required
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                    >
                        <option value="">Выберите категорию</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium mb-2">Описание</label>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        placeholder="Описание материала..."
                        class="w-full px-3 py-2 border border-border rounded-lg bg-background resize-none"
                    ></textarea>
                </div>

                <!-- File Type -->
                <div>
                    <label class="block text-sm font-medium mb-2">Тип файла *</label>
                    <select
                        v-model="form.file_type"
                        required
                        @change="handleFileTypeChange"
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                    >
                        <option value="file">Файл (загрузка или из медиа-библиотеки)</option>
                        <option value="url">Внешняя ссылка</option>
                        <option value="telegram_file_id">Telegram file_id</option>
                    </select>
                </div>

                <!-- File Upload (для типа file) -->
                <div v-if="form.file_type === 'file'">
                    <label class="block text-sm font-medium mb-2">Файл</label>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs text-muted-foreground mb-2">
                                Загрузить новый файл
                            </label>
                            <input
                                ref="fileInput"
                                type="file"
                                @change="handleFileSelect"
                                class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                            />
                            <p v-if="selectedFile" class="text-xs text-muted-foreground mt-1">
                                Выбран: {{ selectedFile.name }}
                            </p>
                        </div>
                        <div class="text-center text-sm text-muted-foreground">или</div>
                        <div>
                            <label class="block text-xs text-muted-foreground mb-2">
                                Выбрать из медиа-библиотеки
                            </label>
                            <button
                                type="button"
                                @click="showMediaPicker = true"
                                class="w-full h-10 px-4 border border-border rounded-lg bg-background hover:bg-muted/10"
                            >
                                {{ form.media_id ? 'Файл выбран' : 'Выбрать файл' }}
                            </button>
                            <p v-if="selectedMedia" class="text-xs text-muted-foreground mt-1">
                                Выбран: {{ selectedMedia.name }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- URL (для типа url) -->
                <div v-if="form.file_type === 'url'">
                    <label class="block text-sm font-medium mb-2">URL *</label>
                    <input
                        v-model="form.file_url"
                        type="url"
                        required
                        placeholder="https://example.com/file.pdf"
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                    />
                </div>

                <!-- Telegram file_id (для типа telegram_file_id) -->
                <div v-if="form.file_type === 'telegram_file_id'">
                    <label class="block text-sm font-medium mb-2">Telegram file_id *</label>
                    <input
                        v-model="form.file_id"
                        type="text"
                        required
                        placeholder="BQACAgIAAxkBAAI..."
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background font-mono text-sm"
                    />
                    <p class="text-xs text-muted-foreground mt-1">
                        Получить file_id можно отправив файл боту и проверив ответ API
                    </p>
                </div>

                <!-- Order Index -->
                <div>
                    <label class="block text-sm font-medium mb-2">Порядок отображения</label>
                    <input
                        v-model.number="form.order_index"
                        type="number"
                        min="0"
                        placeholder="0"
                        class="w-full h-10 px-3 border border-border rounded-lg bg-background"
                    />
                </div>

                <!-- Is Active -->
                <div>
                    <label class="flex items-center gap-2">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="w-4 h-4"
                        />
                        <span>Материал активен</span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex gap-4 pt-4 border-t border-border">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="flex-1 h-10 px-4 border border-border bg-background/50 hover:bg-accent/10 rounded-lg"
                    >
                        Отмена
                    </button>
                    <button
                        type="submit"
                        :disabled="saving"
                        class="flex-1 h-10 px-4 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg disabled:opacity-50"
                    >
                        {{ saving ? 'Сохранение...' : 'Сохранить' }}
                    </button>
                </div>
            </form>

            <!-- Media Picker Modal (упрощенная версия) -->
            <div v-if="showMediaPicker" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/90 p-4">
                <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-border">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold">Выберите файл из медиа-библиотеки</h3>
                            <button @click="showMediaPicker = false" class="text-muted-foreground hover:text-foreground">
                                ✕
                            </button>
                        </div>
                        <input
                            v-model="mediaSearch"
                            type="text"
                            placeholder="Поиск файла..."
                            class="w-full mt-4 h-10 px-3 border border-border rounded-lg bg-background"
                        />
                    </div>
                    <div class="flex-1 overflow-y-auto p-6">
                        <div v-if="loadingMedia" class="text-center py-12">
                            <p class="text-muted-foreground">Загрузка файлов...</p>
                        </div>
                        <div v-else-if="mediaFiles.length === 0" class="text-center py-12 text-muted-foreground">
                            Файлы не найдены
                        </div>
                        <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div
                                v-for="media in mediaFiles"
                                :key="media.id"
                                @click="selectMedia(media)"
                                :class="[
                                    'p-4 border rounded-lg cursor-pointer transition-colors',
                                    form.media_id === media.id
                                        ? 'border-accent bg-accent/10'
                                        : 'border-border hover:bg-muted/10'
                                ]"
                            >
                                <div class="text-sm font-medium truncate">{{ media.name }}</div>
                                <div class="text-xs text-muted-foreground mt-1">{{ formatFileSize(media.size) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 border-t border-border">
                        <button
                            @click="confirmMediaSelection"
                            :disabled="!form.media_id"
                            class="w-full h-10 px-4 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg disabled:opacity-50"
                        >
                            Выбрать
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, watch } from 'vue'
import { apiGet, apiPost, apiPut } from '../../utils/api'
import Swal from 'sweetalert2'

export default {
    name: 'MaterialForm',
    props: {
        botId: {
            type: [String, Number],
            required: true,
        },
        categoryId: {
            type: [String, Number],
            default: null,
        },
        material: {
            type: Object,
            default: null,
        },
    },
    emits: ['close', 'saved'],
    setup(props, { emit }) {
        const saving = ref(false)
        const loadingMedia = ref(false)
        const showMediaPicker = ref(false)
        const mediaSearch = ref('')
        const mediaFiles = ref([])
        const selectedFile = ref(null)
        const selectedMedia = ref(null)
        const fileInput = ref(null)

        const isEditMode = ref(!!props.material)

        const categories = ref([])
        const form = ref({
            title: '',
            category_id: props.categoryId || null,
            description: '',
            file_type: 'file',
            file_url: '',
            file_id: '',
            media_id: null,
            order_index: 0,
            is_active: true,
        })

        const fetchCategories = async () => {
            try {
                const response = await apiGet(`/bot-management/${props.botId}/materials/categories`)
                if (response.ok) {
                    const data = await response.json()
                    categories.value = data.data || []
                }
            } catch (err) {
                console.error('Error fetching categories:', err)
            }
        }

        const fetchMediaFiles = async () => {
            loadingMedia.value = true
            try {
                const params = {}
                if (mediaSearch.value) {
                    params.search = mediaSearch.value
                }
                const response = await apiGet('/media', params)
                if (response.ok) {
                    const data = await response.json()
                    mediaFiles.value = data.data || []
                }
            } catch (err) {
                console.error('Error fetching media:', err)
            } finally {
                loadingMedia.value = false
            }
        }

        const handleFileTypeChange = () => {
            // Очищаем поля файла при смене типа
            form.value.file_url = ''
            form.value.file_id = ''
            form.value.media_id = null
            selectedFile.value = null
            selectedMedia.value = null
        }

        const handleFileSelect = (event) => {
            const file = event.target.files[0]
            if (file) {
                selectedFile.value = file
                form.value.media_id = null
                selectedMedia.value = null
            }
        }

        const selectMedia = (media) => {
            form.value.media_id = media.id
            selectedMedia.value = media
            selectedFile.value = null
        }

        const confirmMediaSelection = () => {
            if (form.value.media_id) {
                showMediaPicker.value = false
            }
        }

        const formatFileSize = (bytes) => {
            if (!bytes) return '0 B'
            const k = 1024
            const sizes = ['B', 'KB', 'MB', 'GB']
            const i = Math.floor(Math.log(bytes) / Math.log(k))
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
        }

        const saveMaterial = async () => {
            saving.value = true
            try {
                const formData = new FormData()
                formData.append('title', form.value.title)
                formData.append('category_id', form.value.category_id)
                formData.append('description', form.value.description || '')
                formData.append('file_type', form.value.file_type)
                formData.append('order_index', form.value.order_index || 0)
                formData.append('is_active', form.value.is_active ? 1 : 0)

                if (form.value.file_type === 'file') {
                    if (selectedFile.value) {
                        formData.append('file', selectedFile.value)
                    } else if (form.value.media_id) {
                        formData.append('media_id', form.value.media_id)
                    } else {
                        throw new Error('Необходимо выбрать файл или указать файл из медиа-библиотеки')
                    }
                } else if (form.value.file_type === 'url') {
                    formData.append('file_url', form.value.file_url)
                } else if (form.value.file_type === 'telegram_file_id') {
                    formData.append('file_id', form.value.file_id)
                }

                let response
                if (isEditMode.value) {
                    response = await apiPut(`/bot-management/${props.botId}/materials/${props.material.id}`, formData)
                } else {
                    response = await apiPost(`/bot-management/${props.botId}/materials`, formData)
                }

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}))
                    throw new Error(errorData.message || 'Ошибка сохранения материала')
                }

                await Swal.fire({
                    title: 'Сохранено',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end',
                })

                emit('saved')
                emit('close')
            } catch (err) {
                Swal.fire({
                    title: 'Ошибка',
                    text: err.message || 'Ошибка сохранения материала',
                    icon: 'error',
                    confirmButtonText: 'ОК',
                })
            } finally {
                saving.value = false
            }
        }

        // Инициализация формы при редактировании
        if (props.material) {
            form.value = {
                title: props.material.title || '',
                category_id: props.material.category_id || props.categoryId || null,
                description: props.material.description || '',
                file_type: props.material.file_type || 'file',
                file_url: props.material.file_url || '',
                file_id: props.material.file_id || '',
                media_id: props.material.media_id || null,
                order_index: props.material.order_index || 0,
                is_active: props.material.is_active !== undefined ? props.material.is_active : true,
            }
            if (props.material.media) {
                selectedMedia.value = props.material.media
            }
        }

        watch(showMediaPicker, (value) => {
            if (value) {
                fetchMediaFiles()
            }
        })

        watch(mediaSearch, () => {
            if (showMediaPicker.value) {
                fetchMediaFiles()
            }
        })

        onMounted(() => {
            fetchCategories()
        })

        return {
            saving,
            loadingMedia,
            showMediaPicker,
            mediaSearch,
            mediaFiles,
            selectedFile,
            selectedMedia,
            fileInput,
            isEditMode,
            categories,
            form,
            fetchMediaFiles,
            handleFileTypeChange,
            handleFileSelect,
            selectMedia,
            confirmMediaSelection,
            formatFileSize,
            saveMaterial,
        }
    },
}
</script>

