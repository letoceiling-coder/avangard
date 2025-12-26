<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-6xl max-h-[90vh] flex flex-col">
      <!-- Header -->
      <div class="flex items-center justify-between p-4 border-b border-border">
        <div class="flex items-center gap-3">
          <button
            @click="handleClose"
            class="h-10 w-10 flex items-center justify-center rounded-lg hover:bg-accent/10 hover:text-accent transition-colors"
          >
            ←
          </button>
          <h2 class="text-xl font-semibold">Редактировать изображение</h2>
          <span v-if="file" class="text-sm text-muted-foreground">
            {{ file.original_name }}
          </span>
        </div>
        <div class="flex gap-2">
          <button
            @click="handleClose"
            class="px-4 py-2 border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors"
          >
            Отмена
          </button>
          <button
            @click="showSaveOptions = true"
            :disabled="saving"
            class="px-4 py-2 bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors disabled:opacity-50"
          >
            {{ saving ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="flex-1 overflow-auto p-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <!-- Cropper Area -->
          <div class="lg:col-span-2">
            <div class="bg-muted/30 rounded-lg p-4">
              <Cropper
                ref="cropperRef"
                :src="imageUrl"
                :stencil-props="{
                  aspectRatio: aspectRatio,
                  resizable: true,
                  movable: true
                }"
                :default-size="{
                  width: defaultWidth,
                  height: defaultHeight
                }"
                image-restriction="stencil"
                class="cropper"
                @change="onChange"
              />
            </div>

            <!-- Crop Info -->
            <div class="mt-4 p-3 bg-muted/30 rounded-lg">
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="text-muted-foreground">Исходный размер:</span>
                  <span class="ml-2 font-medium">{{ originalWidth }} × {{ originalHeight }} px</span>
                </div>
                <div>
                  <span class="text-muted-foreground">Обрезанный размер:</span>
                  <span class="ml-2 font-medium">{{ croppedWidth }} × {{ croppedHeight }} px</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Controls Sidebar -->
          <div class="space-y-4">
            <!-- Aspect Ratio -->
            <div class="bg-muted/30 rounded-lg p-4">
              <h3 class="text-sm font-semibold mb-3">Соотношение сторон</h3>
              <div class="space-y-2">
                <label class="flex items-center gap-2">
                  <input
                    type="radio"
                    :value="null"
                    v-model="aspectRatio"
                    class="w-4 h-4"
                  />
                  <span class="text-sm">Свободное</span>
                </label>
                <label class="flex items-center gap-2">
                  <input
                    type="radio"
                    :value="1"
                    v-model="aspectRatio"
                    class="w-4 h-4"
                  />
                  <span class="text-sm">1:1 (Квадрат)</span>
                </label>
                <label class="flex items-center gap-2">
                  <input
                    type="radio"
                    :value="16/9"
                    v-model="aspectRatio"
                    class="w-4 h-4"
                  />
                  <span class="text-sm">16:9</span>
                </label>
                <label class="flex items-center gap-2">
                  <input
                    type="radio"
                    :value="4/3"
                    v-model="aspectRatio"
                    class="w-4 h-4"
                  />
                  <span class="text-sm">4:3</span>
                </label>
                <label class="flex items-center gap-2">
                  <input
                    type="radio"
                    :value="3/2"
                    v-model="aspectRatio"
                    class="w-4 h-4"
                  />
                  <span class="text-sm">3:2</span>
                </label>
              </div>
            </div>

            <!-- Zoom -->
            <div class="bg-muted/30 rounded-lg p-4">
              <h3 class="text-sm font-semibold mb-3">Масштаб</h3>
              <div class="space-y-2">
                <div class="flex items-center gap-2">
                  <button
                    @click="zoomOut"
                    class="h-8 w-8 flex items-center justify-center bg-background border border-border rounded hover:bg-accent/10"
                  >
                    −
                  </button>
                  <input
                    type="range"
                    v-model.number="zoom"
                    min="0.1"
                    max="3"
                    step="0.1"
                    @input="handleZoomChange"
                    class="flex-1"
                  />
                  <button
                    @click="zoomIn"
                    class="h-8 w-8 flex items-center justify-center bg-background border border-border rounded hover:bg-accent/10"
                  >
                    +
                  </button>
                </div>
                <div class="text-center text-xs text-muted-foreground">
                  {{ Math.round(zoom * 100) }}%
                </div>
              </div>
            </div>

            <!-- Rotate -->
            <div class="bg-muted/30 rounded-lg p-4">
              <h3 class="text-sm font-semibold mb-3">Поворот</h3>
              <div class="grid grid-cols-2 gap-2">
                <button
                  @click="rotate(-90)"
                  class="h-9 flex items-center justify-center bg-background border border-border rounded hover:bg-accent/10"
                >
                  ↺ -90°
                </button>
                <button
                  @click="rotate(90)"
                  class="h-9 flex items-center justify-center bg-background border border-border rounded hover:bg-accent/10"
                >
                  ↻ +90°
                </button>
                <button
                  @click="rotate(-180)"
                  class="h-9 flex items-center justify-center bg-background border border-border rounded hover:bg-accent/10"
                >
                  ↻ -180°
                </button>
                <button
                  @click="rotate(180)"
                  class="h-9 flex items-center justify-center bg-background border border-border rounded hover:bg-accent/10"
                >
                  ↻ +180°
                </button>
              </div>
            </div>

            <!-- Flip -->
            <div class="bg-muted/30 rounded-lg p-4">
              <h3 class="text-sm font-semibold mb-3">Отразить</h3>
              <div class="grid grid-cols-2 gap-2">
                <button
                  @click="flip(true, false)"
                  class="h-9 flex items-center justify-center bg-background border border-border rounded hover:bg-accent/10"
                >
                  ↔ Горизонтально
                </button>
                <button
                  @click="flip(false, true)"
                  class="h-9 flex items-center justify-center bg-background border border-border rounded hover:bg-accent/10"
                >
                  ↕ Вертикально
                </button>
              </div>
            </div>

            <!-- Output Settings -->
            <div class="bg-muted/30 rounded-lg p-4">
              <h3 class="text-sm font-semibold mb-3">Настройки вывода</h3>
              <div class="space-y-3">
                <div>
                  <label class="text-xs text-muted-foreground mb-1 block">Ширина (px)</label>
                  <input
                    type="number"
                    v-model.number="outputWidth"
                    min="1"
                    class="w-full h-9 px-3 border border-border rounded bg-background"
                  />
                </div>
                <div>
                  <label class="text-xs text-muted-foreground mb-1 block">Высота (px)</label>
                  <input
                    type="number"
                    v-model.number="outputHeight"
                    min="1"
                    class="w-full h-9 px-3 border border-border rounded bg-background"
                  />
                </div>
                <div>
                  <label class="text-xs text-muted-foreground mb-1 block">Качество</label>
                  <input
                    type="range"
                    v-model.number="quality"
                    min="1"
                    max="100"
                    class="w-full"
                  />
                  <div class="text-center text-xs text-muted-foreground mt-1">
                    {{ quality }}%
                  </div>
                </div>
                <div>
                  <label class="text-xs text-muted-foreground mb-1 block">Формат</label>
                  <select
                    v-model="outputFormat"
                    class="w-full h-9 px-3 border border-border rounded bg-background"
                  >
                    <option value="image/jpeg">JPEG</option>
                    <option value="image/png">PNG</option>
                    <option value="image/webp">WebP</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="bg-muted/30 rounded-lg p-4">
              <h3 class="text-sm font-semibold mb-3">Действия</h3>
              <div class="space-y-2">
                <button
                  @click="reset"
                  class="w-full h-9 flex items-center justify-center bg-background border border-border rounded hover:bg-accent/10"
                >
                  Сбросить
                </button>
                <button
                  @click="fitToImage"
                  class="w-full h-9 flex items-center justify-center bg-background border border-border rounded hover:bg-accent/10"
                >
                  Подогнать под изображение
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Save Options Modal -->
    <div v-if="showSaveOptions" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 backdrop-blur-sm">
      <div class="bg-background border border-border rounded-lg shadow-2xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold mb-4">Сохранить изображение</h3>
        <div class="space-y-3">
          <button
            @click="saveImage(true)"
            :disabled="saving"
            class="w-full h-12 flex items-center justify-center bg-accent/10 backdrop-blur-xl text-accent border border-accent/40 hover:bg-accent/20 rounded-lg transition-colors disabled:opacity-50"
          >
            Изменить текущее изображение
          </button>
          <button
            @click="saveImage(false)"
            :disabled="saving"
            class="w-full h-12 flex items-center justify-center bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors disabled:opacity-50"
          >
            Создать новое изображение
          </button>
          <button
            @click="showSaveOptions = false"
            class="w-full h-12 flex items-center justify-center border border-border bg-background/50 hover:bg-accent/10 rounded-lg transition-colors"
          >
            Отмена
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch, computed } from 'vue'
import { Cropper } from 'vue-advanced-cropper'
import 'vue-advanced-cropper/dist/style.css'
import { apiPost, apiPut } from '../../utils/api'

export default {
  name: 'ImageEditor',
  components: {
    Cropper
  },
  props: {
    show: {
      type: Boolean,
      default: false
    },
    file: {
      type: Object,
      default: null
    }
  },
  emits: ['close', 'saved'],
  setup(props, { emit }) {
    const cropperRef = ref(null)
    const imageUrl = ref('')
    const aspectRatio = ref(null)
    const zoom = ref(1)
    const outputWidth = ref(800)
    const outputHeight = ref(600)
    const quality = ref(90)
    const outputFormat = ref('image/jpeg')
    const saving = ref(false)
    const showSaveOptions = ref(false)
    const originalWidth = ref(0)
    const originalHeight = ref(0)
    const croppedWidth = ref(0)
    const croppedHeight = ref(0)
    const coordinates = ref(null)

    // Computed
    const defaultWidth = computed(() => props.file?.width || 800)
    const defaultHeight = computed(() => props.file?.height || 600)

    // Watch file changes
    watch(() => props.file, (newFile) => {
      if (newFile && newFile.url) {
        // Если URL относительный, добавляем базовый URL
        imageUrl.value = newFile.url.startsWith('http') ? newFile.url : window.location.origin + newFile.url
        outputWidth.value = newFile.width || 800
        outputHeight.value = newFile.height || 600
        originalWidth.value = newFile.width || 0
        originalHeight.value = newFile.height || 0

        // Определяем формат по расширению
        if (newFile.extension === 'png') {
          outputFormat.value = 'image/png'
        } else if (newFile.extension === 'webp') {
          outputFormat.value = 'image/webp'
        } else {
          outputFormat.value = 'image/jpeg'
        }
      }
    }, { immediate: true })

    // Methods
    const onChange = ({ coordinates: coords, canvas }) => {
      coordinates.value = coords
      if (canvas) {
        croppedWidth.value = Math.round(canvas.width)
        croppedHeight.value = Math.round(canvas.height)
      }
    }

    const handleZoomChange = () => {
      if (cropperRef.value) {
        cropperRef.value.zoom(zoom.value)
      }
    }

    const zoomIn = () => {
      zoom.value = Math.min(3, zoom.value + 0.1)
      handleZoomChange()
    }

    const zoomOut = () => {
      zoom.value = Math.max(0.1, zoom.value - 0.1)
      handleZoomChange()
    }

    const rotate = (angle) => {
      if (cropperRef.value) {
        cropperRef.value.rotate(angle)
      }
    }

    const flip = (horizontal, vertical) => {
      if (cropperRef.value) {
        cropperRef.value.flip(horizontal, vertical)
      }
    }

    const reset = () => {
      zoom.value = 1
      aspectRatio.value = null
      if (cropperRef.value) {
        cropperRef.value.reset()
      }
    }

    const fitToImage = () => {
      if (cropperRef.value && props.file) {
        cropperRef.value.setCoordinates({
          left: 0,
          top: 0,
          width: props.file.width || 800,
          height: props.file.height || 600
        })
      }
    }

    const saveImage = async (replaceExisting) => {
      if (!cropperRef.value || !props.file) {
        return
      }

      saving.value = true
      showSaveOptions.value = false

      try {
        const { canvas } = cropperRef.value.getResult()
        if (!canvas) {
          throw new Error('Не удалось получить результат обрезки')
        }

        // Изменяем размер если указано
        let finalCanvas = canvas
        if (outputWidth.value && outputHeight.value &&
          (canvas.width !== outputWidth.value || canvas.height !== outputHeight.value)) {
          const resizedCanvas = document.createElement('canvas')
          resizedCanvas.width = outputWidth.value
          resizedCanvas.height = outputHeight.value
          const ctx = resizedCanvas.getContext('2d')
          ctx.drawImage(canvas, 0, 0, outputWidth.value, outputHeight.value)
          finalCanvas = resizedCanvas
        }

        // Конвертируем в blob
        const blob = await new Promise((resolve) => {
          finalCanvas.toBlob(resolve, outputFormat.value, quality.value / 100)
        })

        const formData = new FormData()
        formData.append('file', blob, props.file.original_name)
        formData.append('folder_id', props.file.folder_id)

        let response
        if (replaceExisting) {
          // Обновляем существующий файл
          response = await apiPut(`/media/${props.file.id}`, formData)
        } else {
          // Создаем новый файл
          response = await apiPost('/media', formData)
        }

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Ошибка сохранения изображения')
        }

        emit('saved', await response.json())
        handleClose()
      } catch (err) {
        console.error('[ImageEditor] Error saving image:', err)
        alert(err.message || 'Ошибка сохранения изображения')
      } finally {
        saving.value = false
      }
    }

    const handleClose = () => {
      emit('close')
    }

    return {
      cropperRef,
      imageUrl,
      aspectRatio,
      zoom,
      outputWidth,
      outputHeight,
      quality,
      outputFormat,
      saving,
      showSaveOptions,
      originalWidth,
      originalHeight,
      croppedWidth,
      croppedHeight,
      defaultWidth,
      defaultHeight,
      onChange,
      handleZoomChange,
      zoomIn,
      zoomOut,
      rotate,
      flip,
      reset,
      fitToImage,
      saveImage,
      handleClose
    }
  }
}
</script>

<style scoped>
.cropper {
  width: 100%;
  height: 500px;
  background: #f0f0f0;
}
</style>
