<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParserError;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParserErrorController extends Controller
{
    /**
     * Список ошибок парсера
     */
    public function index(Request $request)
    {
        $query = ParserError::query()
            ->with(['user', 'resolvedBy'])
            ->orderBy('created_at', 'desc');
        
        // Фильтры
        if ($request->has('error_type')) {
            $query->where('error_type', $request->error_type);
        }
        
        if ($request->has('object_type')) {
            $query->where('object_type', $request->object_type);
        }
        
        if ($request->has('is_resolved')) {
            $query->where('is_resolved', filter_var($request->is_resolved, FILTER_VALIDATE_BOOLEAN));
        }
        
        $perPage = min(max($request->get('per_page', 20), 1), 100);
        $errors = $query->paginate($perPage);
        
        return response()->json($errors);
    }
    
    /**
     * Просмотр ошибки
     */
    public function show(ParserError $parserError)
    {
        $parserError->load(['user', 'resolvedBy']);
        
        return response()->json($parserError);
    }
    
    /**
     * Пометить ошибку как решенную
     */
    public function resolve(Request $request, ParserError $parserError)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);
        
        $parserError->markAsResolved(
            Auth::id(),
            $request->input('notes')
        );
        
        return response()->json([
            'message' => 'Ошибка помечена как решенная',
            'error' => $parserError->fresh(['resolvedBy']),
        ]);
    }
    
    /**
     * Статистика ошибок
     */
    public function statistics()
    {
        $stats = [
            'total' => ParserError::count(),
            'unresolved' => ParserError::unresolved()->count(),
            'by_type' => ParserError::selectRaw('error_type, COUNT(*) as count')
                ->groupBy('error_type')
                ->pluck('count', 'error_type'),
            'by_object_type' => ParserError::selectRaw('object_type, COUNT(*) as count')
                ->whereNotNull('object_type')
                ->groupBy('object_type')
                ->pluck('count', 'object_type'),
            'recent' => ParserError::where('created_at', '>=', now()->subDays(7))->count(),
        ];
        
        return response()->json($stats);
    }
}

