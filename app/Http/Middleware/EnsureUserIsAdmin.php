<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user || !$user->hasAnyRole(['admin'])) {
            // Логирование неавторизованного доступа к поддержке
            if (str_contains($request->path(), 'support')) {
                \App\Services\SupportLogger::logUnauthorizedAccess('Admin access to support', [
                    'path' => $request->path(),
                    'user_id' => $user?->id,
                ]);
            }
            
            return response()->json([
                'message' => 'Доступ запрещен. Требуется роль администратора.',
            ], 403);
        }
        
        return $next($request);
    }
}
