<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Media",
 *     description="API для управления медиа-файлами"
 * )
 */
class MediaController extends Controller
{
    /**
     * Конструктор контроллера
     * 
     * Middleware auth:api закомментирован для разработки
     * В продакшене раскомментировать!
     */
    public function __construct()
    {
        // TODO: Раскомментировать в продакшене
        // $this->middleware('auth:api', ['only' => ['store', 'update', 'destroy']]);
    }

    /**
     * Получить список медиа-файлов
     * 
     * @OA\Get(
     *     path="/media",
     *     tags={"Media"},
     *     summary="Получить список медиа-файлов",
     *     description="Возвращает список файлов с возможностью фильтрации, сортировки и пагинации",
     *     @OA\Parameter(
     *         name="folder_id",
     *         in="query",
     *         description="ID папки для фильтрации (null для корневых файлов)",
     *         required=false,
     *         @OA\Schema(type="integer", nullable=true)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Поиск по имени файла",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Сортировка (newest, oldest, name_asc, name_desc, size_asc, size_desc)",
     *         required=false,
     *         @OA\Schema(type="string", default="newest")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Количество элементов на странице",
     *         required=false,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Номер страницы",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Media"))
     *         )
     *     )
     * )
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Media::query();

        // Получаем папку корзины для проверки
        $trashFolder = Folder::getTrashFolder();
        $isTrashFolder = false;

        // Фильтрация по папке
        if ($request->has('folder_id')) {
            $folderId = $request->get('folder_id');

            // Проверяем на null, 'null', '' и 0
            if ($folderId === null || $folderId === 'null' || $folderId === '' || $folderId === 0 || $folderId === '0') {
                $query->whereNull('folder_id');
            } else {
                $query->where('folder_id', $folderId);
                
                // Проверяем, является ли запрашиваемая папка корзиной
                if ($trashFolder && ($folderId == $trashFolder->id || $folderId == 4)) {
                    $isTrashFolder = true;
                }
            }
        } else {
            // Если параметр folder_id не передан, показываем только корневые файлы
            $query->whereNull('folder_id');
        }

        // Для корзины показываем все файлы (включая с deleted_at)
        // Для обычных папок исключаем файлы с deleted_at (мягко удаленные)
        if (!$isTrashFolder) {
            $query->whereNull('deleted_at');
        }

        // Фильтрация по original_folder_id (для удаленных папок в корзине)
        if ($request->has('original_folder_id') && $request->get('original_folder_id')) {
            $originalFolderId = $request->get('original_folder_id');
            $query->where('original_folder_id', $originalFolderId);
        }

        // Поиск по имени и расширению
        if ($request->has('search') && $request->get('search')) {
            $search = trim($request->get('search'));
            $query->where(function($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('extension', 'like', "%{$search}%");
            });
        }

        // Фильтрация по типу файла
        if ($request->has('type') && $request->get('type')) {
            $type = $request->get('type');
            $query->where('type', $type);
        }

        // Фильтрация по расширению
        if ($request->has('extension') && $request->get('extension')) {
            $extension = $request->get('extension');
            $query->where('extension', $extension);
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['name', 'original_name', 'size', 'type', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Пагинация
        $perPage = (int) $request->get('per_page', config('media.pagination.per_page_default', 20));
        $perPageMax = config('media.pagination.per_page_max', 100);
        
        // Ограничиваем максимальное количество на странице
        if ($perPage > $perPageMax) {
            $perPage = $perPageMax;
        }
        
        // Минимум 1 файл на странице
        if ($perPage < 1) {
            $perPage = config('media.pagination.per_page_default', 20);
        }

        // Если запрошена пагинация (по умолчанию включена)
        return MediaResource::collection(
            $query->paginate($perPage)
        );
    }

    /**
     * Загрузить файл
     * 
     * @OA\Post(
     *     path="/media",
     *     tags={"Media"},
     *     summary="Загрузить медиа-файл",
     *     description="Загружает файл в систему с возможностью указания папки",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Файл для загрузки (jpg, jpeg, png, gif, webp, svg, pdf, doc, docx, mp4, avi, mov)"
     *                 ),
     *                 @OA\Property(
     *                     property="folder_id",
     *                     type="integer",
     *                     nullable=true,
     *                     description="ID папки для загрузки"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Файл успешно загружен",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Файл успешно загружен"),
     *             @OA\Property(property="data", ref="#/components/schemas/Media")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации"),
     *     @OA\Response(response=500, description="Ошибка сервера")
     * )
     * 
     * @param StoreMediaRequest $request
     * @return JsonResponse
     */
    public function store(StoreMediaRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $folderId = $request->input('folder_id');
            
            // Запрещаем загрузку файлов напрямую в корзину (ID = 4)
            if ($folderId == 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзя загружать файлы напрямую в корзину.'
                ], 403);
            }
            
            // Дополнительная проверка через модель Folder
            if ($folderId) {
                $targetFolder = Folder::find($folderId);
                if ($targetFolder && $targetFolder->is_trash) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Нельзя загружать файлы в корзину.'
                    ], 403);
                }
            }

            // Получаем информацию о файле
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize();

            // Генерируем уникальное имя
            $fileName = uniqid() . '_' . time() . '.' . $extension;

            // Определяем тип файла
            $type = $this->getFileType($mimeType);

            // Определяем путь для сохранения
            $uploadPath = 'upload';
            if ($folderId) {
                $folder = Folder::find($folderId);
                if ($folder) {
                    // Создаём путь из иерархии папок
                    $folderPath = $this->getFolderPath($folder);
                    $uploadPath = 'upload/' . $folderPath;
                }
            }

            // Создаём директорию если не существует
            $fullPath = public_path($uploadPath);
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Сохраняем файл
            $file->move($fullPath, $fileName);
            $relativePath = $uploadPath . '/' . $fileName;

            // Получаем размеры изображения
            $width = null;
            $height = null;
            if ($type === 'photo') {
                $imagePath = public_path($relativePath);
                $imageInfo = @getimagesize($imagePath);
                if ($imageInfo !== false) {
                    $width = $imageInfo[0];
                    $height = $imageInfo[1];
                }
            }

            // Сохраняем в БД
            $media = Media::create([
                'name' => $fileName,
                'original_name' => $originalName,
                'extension' => $extension,
                'disk' => $uploadPath,
                'width' => $width,
                'height' => $height,
                'type' => $type,
                'size' => $fileSize,
                'folder_id' => $folderId,
                'user_id' => auth()->check() ? auth()->id() : null,
                'temporary' => false,
                'metadata' => json_encode([
                    'path' => $relativePath,
                    'mime_type' => $mimeType
                ])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Файл успешно загружен',
                'data' => new MediaResource($media)
            ]);

        } catch (\Exception $e) {
            Log::error('Media upload error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка загрузки файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить информацию о файле
     * 
     * @OA\Get(
     *     path="/media/{id}",
     *     tags={"Media"},
     *     summary="Получить информацию о файле",
     *     description="Возвращает детальную информацию о конкретном медиа-файле",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID файла",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(ref="#/components/schemas/Media")
     *     ),
     *     @OA\Response(response=404, description="Файл не найден")
     * )
     * 
     * @param string $id
     * @return MediaResource
     */
    public function show(string $id): MediaResource
    {
        $media = Media::with(['folder', 'user'])->findOrFail($id);
        return new MediaResource($media);
    }

    /**
     * Обновить файл (переместить в другую папку)
     * 
     * @OA\Put(
     *     path="/media/{id}",
     *     tags={"Media"},
     *     summary="Обновить медиа-файл",
     *     description="Обновляет данные файла (перемещение в другую папку)",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID файла",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="folder_id", type="integer", nullable=true, description="ID новой папки")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Файл успешно обновлён",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Файл успешно перемещён"),
     *             @OA\Property(property="data", ref="#/components/schemas/Media")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Файл не найден"),
     *     @OA\Response(response=422, description="Ошибка валидации"),
     *     @OA\Response(response=500, description="Ошибка сервера")
     * )
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $media = Media::findOrFail($id);

        $request->validate([
            'folder_id' => 'nullable|exists:folders,id',
            'file' => 'nullable|file|max:10240' // 10MB, для замены файла
        ]);

        try {
            // Получаем folder_id, преобразуя пустую строку в null
            $newFolderId = $request->input('folder_id');
            if ($newFolderId === '' || $newFolderId === 'null') {
                $newFolderId = null;
            }
            $newFile = $request->file('file');
            
            // Если загружен новый файл, заменяем существующий
            if ($newFile) {
                // Удаляем старый файл
                $oldPath = public_path($media->disk . '/' . $media->name);
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
                
                // Получаем информацию о новом файле
                $originalName = $newFile->getClientOriginalName();
                $extension = $newFile->getClientOriginalExtension();
                $mimeType = $newFile->getClientMimeType();
                $fileSize = $newFile->getSize();
                
                // Генерируем уникальное имя
                $fileName = uniqid() . '_' . time() . '.' . $extension;
                
                // Определяем тип файла
                $type = $this->getFileType($mimeType);
                
                // Определяем путь для сохранения
                $uploadPath = $media->disk;
                if ($newFolderId !== null) {
                    $folder = Folder::find($newFolderId);
                    if ($folder) {
                        $folderPath = $this->getFolderPath($folder);
                        $uploadPath = 'upload/' . $folderPath;
                    }
                }
                
                // Создаём директорию если не существует
                $fullPath = public_path($uploadPath);
                if (!file_exists($fullPath)) {
                    mkdir($fullPath, 0755, true);
                }
                
                // Сохраняем новый файл
                $newFile->move($fullPath, $fileName);
                $relativePath = $uploadPath . '/' . $fileName;
                
                // Получаем размеры изображения
                $width = null;
                $height = null;
                if ($type === 'photo') {
                    $imagePath = public_path($relativePath);
                    $imageInfo = @getimagesize($imagePath);
                    if ($imageInfo !== false) {
                        $width = $imageInfo[0];
                        $height = $imageInfo[1];
                    }
                }
                
                // Обновляем метаданные
                $metadata = $media->metadata ? json_decode($media->metadata, true) : [];
                $metadata['path'] = $relativePath;
                $metadata['mime_type'] = $mimeType;
                
                // Обновляем запись в БД
                $media->update([
                    'name' => $fileName,
                    'original_name' => $originalName,
                    'extension' => $extension,
                    'disk' => $uploadPath,
                    'width' => $width,
                    'height' => $height,
                    'type' => $type,
                    'size' => $fileSize,
                    'folder_id' => $newFolderId !== null ? $newFolderId : $media->folder_id,
                    'metadata' => json_encode($metadata)
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Файл успешно обновлён',
                    'data' => new MediaResource($media->fresh())
                ]);
            }
            
            // Запрещаем прямое перемещение файлов в корзину (ID = 4)
            if ($newFolderId == 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзя перемещать файлы напрямую в корзину. Используйте функцию удаления.'
                ], 403);
            }
            
            // Дополнительная проверка через модель Folder
            if ($newFolderId) {
                $targetFolder = Folder::find($newFolderId);
                if ($targetFolder && $targetFolder->is_trash) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Нельзя перемещать файлы в корзину. Используйте функцию удаления.'
                    ], 403);
                }
            }

            // Если папка изменилась, перемещаем физический файл
            // Сравниваем с учетом null (строгое сравнение для корректной работы с null)
            $currentFolderId = $media->folder_id;
            if (($newFolderId !== $currentFolderId) || 
                (is_null($newFolderId) && !is_null($currentFolderId)) || 
                (!is_null($newFolderId) && is_null($currentFolderId))) {
                
                $oldPath = public_path($media->disk . '/' . $media->name);

                // Определяем новый путь
                $newUploadPath = 'upload';
                if ($newFolderId) {
                    $folder = Folder::find($newFolderId);
                    if ($folder) {
                        $folderPath = $this->getFolderPath($folder);
                        $newUploadPath = 'upload/' . $folderPath;
                    }
                }

                // Создаём новую директорию если не существует
                $newFullPath = public_path($newUploadPath);
                if (!file_exists($newFullPath)) {
                    mkdir($newFullPath, 0755, true);
                }

                // Перемещаем файл
                $newFilePath = $newFullPath . '/' . $media->name;
                if (file_exists($oldPath)) {
                    rename($oldPath, $newFilePath);
                }

                // Обновляем метаданные в БД
                $metadata = $media->metadata ? json_decode($media->metadata, true) : [];
                $metadata['path'] = $newUploadPath . '/' . $media->name;

                $media->update([
                    'folder_id' => $newFolderId,
                    'disk' => $newUploadPath,
                    'metadata' => json_encode($metadata)
                ]);

                Log::info('File moved', [
                    'file' => $media->name,
                    'from' => $oldPath,
                    'to' => $newFilePath
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Файл успешно перемещён',
                'data' => new MediaResource($media)
            ]);

        } catch (\Exception $e) {
            Log::error('Media move error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка перемещения файла',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить файл
     * 
     * @OA\Delete(
     *     path="/media/{id}",
     *     tags={"Media"},
     *     summary="Удалить файл",
     *     description="Удаляет медиа-файл из системы и БД",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID файла",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Файл успешно удалён",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Файл успешно удалён")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Файл не найден"),
     *     @OA\Response(response=500, description="Ошибка сервера")
     * )
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $media = Media::findOrFail($id);
            
            // Получаем папку корзины
            $trashFolder = Folder::getTrashFolder();
            
            if (!$trashFolder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Корзина не найдена'
                ], 404);
            }

            // Если файл уже в корзине - удаляем физически и из БД
            if ($media->folder_id == $trashFolder->id) {
                // Получаем метаданные для правильного пути
                $metadata = $media->metadata ? json_decode($media->metadata, true) : [];
                $filePath = public_path($metadata['path'] ?? ($media->disk . '/' . $media->name));

                // Удаляем физический файл
                if (file_exists($filePath) && is_file($filePath)) {
                    unlink($filePath);
                    Log::info('File permanently deleted from filesystem', ['path' => $filePath]);
                }

                // Удаляем запись из БД
                $media->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Файл безвозвратно удалён',
                    'permanently_deleted' => true
                ]);
            }

            // Сохраняем оригинальную папку для восстановления
            $media->original_folder_id = $media->folder_id;
            $media->folder_id = $trashFolder->id;
            $media->deleted_at = now();
            $media->save();

            Log::info('File moved to trash', [
                'media_id' => $media->id,
                'from_folder' => $media->original_folder_id,
                'to_trash' => $trashFolder->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Файл перемещён в корзину',
                'moved_to_trash' => true,
                'data' => new MediaResource($media)
            ]);
        } catch (\Exception $e) {
            Log::error('Media deletion error', [
                'media_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления файла'
            ], 500);
        }
    }

    /**
     * Восстановить файл из корзины
     * 
     * @OA\Post(
     *     path="/media/{id}/restore",
     *     tags={"Media"},
     *     summary="Восстановить файл из корзины",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Файл успешно восстановлен"
     *     )
     * )
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $media = Media::findOrFail($id);
            
            // Проверяем что файл в корзине
            $trashFolder = Folder::getTrashFolder();
            if ($media->folder_id != $trashFolder->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Файл не находится в корзине'
                ], 400);
            }
            
            // Восстанавливаем в оригинальную папку
            $media->folder_id = $media->original_folder_id;
            $media->original_folder_id = null;
            $media->deleted_at = null;
            $media->save();
            
            Log::info('File restored from trash', [
                'media_id' => $media->id,
                'restored_to_folder' => $media->folder_id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Файл успешно восстановлен',
                'data' => new MediaResource($media)
            ]);
        } catch (\Exception $e) {
            Log::error('Media restore error', [
                'media_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка восстановления файла'
            ], 500);
        }
    }
    
    /**
     * Очистить корзину
     * 
     * @OA\Delete(
     *     path="/media/trash/empty",
     *     tags={"Media"},
     *     summary="Очистить корзину",
     *     @OA\Response(
     *         response=200,
     *         description="Корзина успешно очищена"
     *     )
     * )
     */
    public function emptyTrash(): JsonResponse
    {
        try {
            $trashFolder = Folder::getTrashFolder();
            
            if (!$trashFolder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Корзина не найдена'
                ], 404);
            }
            
            // Получаем все файлы из корзины
            $trashFiles = Media::where('folder_id', $trashFolder->id)->get();
            $deletedCount = 0;
            
            foreach ($trashFiles as $media) {
                // Получаем метаданные для правильного пути
                $metadata = $media->metadata ? json_decode($media->metadata, true) : [];
                $filePath = public_path($metadata['path'] ?? ($media->disk . '/' . $media->name));
                
                // Удаляем физический файл
                if (file_exists($filePath) && is_file($filePath)) {
                    unlink($filePath);
                }
                
                // Удаляем запись из БД
                $media->delete();
                $deletedCount++;
            }
            
            Log::info('Trash emptied', ['deleted_files' => $deletedCount]);
            
            return response()->json([
                'success' => true,
                'message' => "Корзина очищена. Удалено файлов: $deletedCount",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Empty trash error', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка очистки корзины'
            ], 500);
        }
    }

    /**
     * Получить путь к папке из иерархии
     */
    private function getFolderPath(Folder $folder): string
    {
        $path = [];
        $currentFolder = $folder;

        // Загружаем родителей для построения пути
        while ($currentFolder) {
            array_unshift($path, Str::slug($currentFolder->name));
            $currentFolder = $currentFolder->parent;
        }

        return implode('/', $path);
    }

    /**
     * Определить тип файла по MIME type
     */
    private function getFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'photo';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        return 'document';
    }
}

