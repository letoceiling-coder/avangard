<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Filters\FolderFilter;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Http\Resources\FolderResource;
use App\Models\Folder;
use App\Models\Media;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Folders",
 *     description="API для управления папками медиа-менеджера"
 * )
 */
class FolderController extends Controller
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
     * Получить список папок
     * 
     * @OA\Get(
     *     path="/folders",
     *     tags={"Folders"},
     *     summary="Получить список папок",
     *     description="Возвращает список папок с возможностью фильтрации по родительской папке и пагинации",
     *     @OA\Parameter(
     *         name="parent_id",
     *         in="query",
     *         description="ID родительской папки (null для корневых папок)",
     *         required=false,
     *         @OA\Schema(type="integer", nullable=true)
     *     ),
     *     @OA\Parameter(
     *         name="paginate",
     *         in="query",
     *         description="Количество элементов на странице (0 - без пагинации)",
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
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Folder"))
     *         )
     *     )
     * )
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws BindingResolutionException
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filter = app()->make(FolderFilter::class, ['queryParams' => array_filter($request->all())]);

        // Получаем папку корзины для проверки
        $trashFolder = Folder::getTrashFolder();
        $isTrashFolder = false;

        // Проверяем, запрашивается ли корзина
        // Если trash=1, показываем все удаленные папки независимо от parent_id
        if ($request->has('trash') && $request->get('trash') == '1') {
            $isTrashFolder = true;
        } elseif ($request->has('parent_id')) {
            $parentId = $request->get('parent_id');
            // Проверяем, является ли запрашиваемая папка корзиной
            if ($trashFolder && ($parentId == $trashFolder->id || $parentId == 4)) {
                $isTrashFolder = true;
            }
        }

        if ($request->has('paginate') && $request->get('paginate') != 0) {
            // Для корзины используем withTrashed() чтобы включить удаленные записи
            if ($isTrashFolder) {
                $query = Folder::withTrashed()->filter($filter);
                $query->whereNotNull('deleted_at');
            } else {
                $query = Folder::filter($filter);
                // SoftDeletes автоматически исключает удаленные
            }
            
            return FolderResource::collection(
                $query->paginate(
                    $request->get('paginate'), 
                    ['*'], 
                    'page', 
                    $request->get('page') ?? 1
                )
            );
        }

        // Для корзины показываем ВСЕ удаленные папки, независимо от parent_id
        if ($isTrashFolder) {
            // Используем withTrashed() чтобы включить удаленные записи
            $query = Folder::withTrashed()->filter($filter)->with('children');
            $query->whereNotNull('deleted_at');
            
            // Если указан parent_id, фильтруем удаленные папки по родителю (для вложенных удаленных папок)
            if ($request->has('parent_id')) {
                $parentId = $request->get('parent_id');
                if ($parentId !== null && $parentId !== 'null' && $parentId !== '' && $parentId != 4) {
                    $query->where('parent_id', $parentId);
                }
            }
        } else {
            // Получаем папки с дочерними элементами (без удаленных)
            $query = Folder::filter($filter)->with('children');
            
            // Фильтрация по родительской папке для обычных папок
            if ($request->has('parent_id')) {
                $parentId = $request->get('parent_id');
                if ($parentId === null || $parentId === 'null' || $parentId === '') {
                    $query->whereNull('parent_id');
                } else {
                    $query->where('parent_id', $parentId);
                }
            } else {
                // По умолчанию - только корневые папки
                $query->whereNull('parent_id');
            }
            
            // Исключаем удаленные папки для обычных запросов (SoftDeletes делает это автоматически)
        }

        // Сортировка по позиции, затем по ID
        $query->orderBy('position', 'asc')->orderBy('id', 'asc');

        return FolderResource::collection($query->get());
    }

    /**
     * Создать новую папку
     * 
     * @OA\Post(
     *     path="/folders",
     *     tags={"Folders"},
     *     summary="Создать новую папку",
     *     description="Создаёт новую папку в медиа-менеджере",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Новая папка", description="Название папки"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=null, description="ID родительской папки"),
     *             @OA\Property(property="slug", type="string", nullable=true, example="novaya-papka", description="URL-slug (генерируется автоматически)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Папка успешно создана",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Folder"))
     *         )
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации")
     * )
     * 
     * @param StoreFolderRequest $request
     * @return AnonymousResourceCollection
     */
    public function store(StoreFolderRequest $request): AnonymousResourceCollection
    {
        try {
            $folder = Folder::create($request->validated());

            // Возвращаем все папки того же уровня для обновления списка
            $query = Folder::with('children')
                ->whereNull('deleted_at')
                ->orderBy('position', 'asc')
                ->orderBy('id', 'asc');
            
            if ($folder->parent_id) {
                $query->where('parent_id', $folder->parent_id);
            } else {
                $query->whereNull('parent_id');
            }

            return FolderResource::collection($query->get());
        } catch (\Exception $e) {
            Log::error('Folder creation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Показать конкретную папку
     * 
     * @OA\Get(
     *     path="/folders/{id}",
     *     tags={"Folders"},
     *     summary="Получить информацию о папке",
     *     description="Возвращает детальную информацию о конкретной папке",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID папки",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(ref="#/components/schemas/Folder")
     *     ),
     *     @OA\Response(response=404, description="Папка не найдена")
     * )
     * 
     * @param string $id
     * @return FolderResource
     */
    public function show(string $id): FolderResource
    {
        // Загружаем папку с родительскими папками рекурсивно для хлебных крошек
        // Используем withTrashed() чтобы можно было загружать удаленные папки (для корзины)
        $folder = Folder::withTrashed()
            ->with(['children', 'parent', 'parent.parent', 'parent.parent.parent', 'files'])
            ->findOrFail($id);
        return new FolderResource($folder);
    }

    /**
     * Обновить папку
     * 
     * @OA\Put(
     *     path="/folders/{id}",
     *     tags={"Folders"},
     *     summary="Обновить папку",
     *     description="Обновляет данные папки",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID папки",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Обновлённая папка"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *             @OA\Property(property="position", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Папка успешно обновлена",
     *         @OA\JsonContent(ref="#/components/schemas/Folder")
     *     ),
     *     @OA\Response(response=404, description="Папка не найдена"),
     *     @OA\Response(response=422, description="Ошибка валидации")
     * )
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $folder = Folder::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|exists:folders,id',
            'position' => 'sometimes|integer|min:0'
        ]);

        try {
            $folder->update($validated);
            $folder->load(['children', 'parent']);

            return response()->json([
                'success' => true,
                'message' => 'Папка успешно обновлена',
                'data' => new FolderResource($folder)
            ]);
        } catch (\Exception $e) {
            Log::error('Folder update error', [
                'folder_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления папки'
            ], 500);
        }
    }

    /**
     * Удалить папку
     * 
     * @OA\Delete(
     *     path="/folders/{id}",
     *     tags={"Folders"},
     *     summary="Удалить папку",
     *     description="Удаляет папку и все её содержимое (файлы и вложенные папки)",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID папки",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Папка успешно удалена",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Папка успешно удалена")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Папка не найдена"),
     *     @OA\Response(response=500, description="Ошибка сервера")
     * )
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            // Проверяем, нужно ли безвозвратное удаление
            $force = $request->get('force', false);
            
            // Если force delete, ищем папку включая удаленные
            if ($force) {
                $folder = Folder::withTrashed()->findOrFail($id);
                
                // Проверяем, что папка действительно удалена
                if (!$folder->trashed()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Папка не находится в корзине. Используйте обычное удаление.'
                    ], 422);
                }
                
                // Безвозвратное удаление
                DB::beginTransaction();
                
                // Рекурсивная функция для безвозвратного удаления папки и её содержимого
                $permanentlyDeleteFolder = function($folderToDelete) use (&$permanentlyDeleteFolder) {
                    // Безвозвратно удаляем все файлы (Media не использует SoftDeletes, поэтому просто delete)
                    $files = Media::where('folder_id', $folderToDelete->id)->get();
                    foreach ($files as $file) {
                        // Удаляем физический файл, если он существует
                        try {
                            $metadata = $file->metadata ? json_decode($file->metadata, true) : [];
                            $path = $metadata['path'] ?? ($file->disk . '/' . $file->name);
                            
                            // Удаляем файл через Storage
                            if (Storage::disk($file->disk)->exists($path)) {
                                Storage::disk($file->disk)->delete($path);
                            }
                        } catch (\Exception $e) {
                            // Логируем ошибку, но продолжаем удаление
                            Log::warning('Error deleting media file', [
                                'file_id' => $file->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                        
                        $file->delete();
                    }
                    
                    // Рекурсивно обрабатываем вложенные папки
                    $children = Folder::withTrashed()->where('parent_id', $folderToDelete->id)->get();
                    foreach ($children as $child) {
                        $permanentlyDeleteFolder($child);
                    }
                    
                    // Безвозвратное удаление папки
                    $folderToDelete->forceDelete();
                };
                
                // Выполняем безвозвратное удаление
                $permanentlyDeleteFolder($folder);
                
                DB::commit();
                
                Log::info('Folder permanently deleted', [
                    'folder_id' => $id,
                    'folder_name' => $folder->name
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Папка безвозвратно удалена'
                ]);
            }
            
            // Обычное удаление (перемещение в корзину)
            $folder = Folder::findOrFail($id);

            // Проверяем, защищена ли папка
            if ($folder->protected) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзя удалить защищенную папку'
                ], 403);
            }

            // Нельзя удалить корзину
            if ($folder->is_trash) {
                return response()->json([
                    'success' => false,
                    'message' => 'Нельзя удалить корзину'
                ], 403);
            }

            // Получаем папку корзины
            $trashFolder = Folder::getTrashFolder();
            if (!$trashFolder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Корзина не найдена'
                ], 404);
            }

            DB::beginTransaction();

            // Рекурсивная функция для перемещения папки и её содержимого в корзину
            $moveFolderToTrash = function($folderToDelete) use ($trashFolder, &$moveFolderToTrash) {
                // Перемещаем все файлы в корзину
                $files = Media::where('folder_id', $folderToDelete->id)->get();
                foreach ($files as $file) {
                    $file->original_folder_id = $file->folder_id;
                    $file->folder_id = $trashFolder->id;
                    $file->deleted_at = now();
                    $file->save();
                }

                // Рекурсивно обрабатываем вложенные папки
                $children = Folder::where('parent_id', $folderToDelete->id)->get();
                foreach ($children as $child) {
                    $moveFolderToTrash($child);
                }

                // Мягкое удаление папки (используем метод delete() из SoftDeletes)
                $folderToDelete->delete();
            };

            // Выполняем перемещение в корзину
            $moveFolderToTrash($folder);

            DB::commit();

            Log::info('Folder moved to trash', [
                'folder_id' => $folder->id,
                'folder_name' => $folder->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Папка перемещена в корзину'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Folder deletion error', [
                'folder_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка удаления папки: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Восстановить папку из корзины
     * 
     * @OA\Post(
     *     path="/folders/{id}/restore",
     *     tags={"Folders"},
     *     summary="Восстановить папку из корзины",
     *     description="Восстанавливает папку и все её содержимое (файлы и вложенные папки) из корзины",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID папки",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Папка успешно восстановлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Папка успешно восстановлена")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Папка не найдена"),
     *     @OA\Response(response=500, description="Ошибка сервера")
     * )
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function restore(string $id): JsonResponse
    {
        try {
            // Загружаем папку включая удаленные
            $folder = Folder::withTrashed()->findOrFail($id);

            // Проверяем, что папка действительно удалена
            if (!$folder->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Папка не находится в корзине'
                ], 400);
            }

            // Получаем папку корзины
            $trashFolder = Folder::getTrashFolder();
            if (!$trashFolder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Корзина не найдена'
                ], 404);
            }

            DB::beginTransaction();

            // Рекурсивная функция для восстановления папки и её содержимого
            $restoreFolderFromTrash = function($folderToRestore) use ($trashFolder, &$restoreFolderFromTrash) {
                // Восстанавливаем все файлы из корзины, которые принадлежали этой папке
                $files = Media::where('folder_id', $trashFolder->id)
                    ->where('original_folder_id', $folderToRestore->id)
                    ->get();
                
                foreach ($files as $file) {
                    $file->folder_id = $file->original_folder_id;
                    $file->original_folder_id = null;
                    $file->deleted_at = null;
                    $file->save();
                }

                // Рекурсивно восстанавливаем вложенные папки
                $children = Folder::withTrashed()
                    ->where('parent_id', $folderToRestore->id)
                    ->whereNotNull('deleted_at')
                    ->get();
                
                foreach ($children as $child) {
                    $restoreFolderFromTrash($child);
                }

                // Восстанавливаем папку
                $folderToRestore->deleted_at = null;
                $folderToRestore->save();
            };

            // Выполняем восстановление
            $restoreFolderFromTrash($folder);

            DB::commit();

            Log::info('Folder restored from trash', [
                'folder_id' => $folder->id,
                'folder_name' => $folder->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Папка успешно восстановлена'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Folder restore error', [
                'folder_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка восстановления папки: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить дерево папок
     * 
     * @OA\Get(
     *     path="/folders/tree/all",
     *     tags={"Folders"},
     *     summary="Получить дерево всех папок",
     *     description="Возвращает иерархическую структуру всех папок в виде дерева",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Folder"))
     *         )
     *     )
     * )
     * 
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function tree(Request $request): AnonymousResourceCollection
    {
        // Получаем все корневые папки с их дочерними элементами
        // Исключаем удаленные папки
        $folders = Folder::with('children')
            ->whereNull('parent_id')
            ->whereNull('deleted_at')
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return FolderResource::collection($folders);
    }

    /**
     * Обновить позиции папок (drag & drop)
     * 
     * @OA\Post(
     *     path="/folders/update-positions",
     *     tags={"Folders"},
     *     summary="Обновить позиции папок",
     *     description="Массовое обновление позиций папок для реализации drag & drop",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"folders"},
     *             @OA\Property(
     *                 property="folders",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="position", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Позиции успешно обновлены",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Позиции папок успешно обновлены")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Ошибка валидации"),
     *     @OA\Response(response=500, description="Ошибка сервера")
     * )
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePositions(Request $request): JsonResponse
    {
        $request->validate([
            'folders' => 'required|array|min:1',
            'folders.*.id' => 'required|exists:folders,id',
            'folders.*.position' => 'required|integer|min:0'
        ], [
            'folders.required' => 'Массив папок обязателен',
            'folders.array' => 'Папки должны быть переданы в виде массива',
            'folders.*.id.required' => 'ID папки обязателен',
            'folders.*.id.exists' => 'Папка с указанным ID не найдена',
            'folders.*.position.required' => 'Позиция обязательна',
            'folders.*.position.integer' => 'Позиция должна быть целым числом'
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->folders as $folderData) {
                Folder::where('id', $folderData['id'])->update([
                    'position' => $folderData['position']
                ]);
            }

            DB::commit();

            Log::info('Folder positions updated', [
                'count' => count($request->folders),
                'folders' => $request->folders
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Позиции папок успешно обновлены'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Folder positions update error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка обновления позиций: ' . $e->getMessage()
            ], 500);
        }
    }
}
