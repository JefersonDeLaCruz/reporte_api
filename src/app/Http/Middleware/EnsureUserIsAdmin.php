<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe la ruta a usuarios con users.is_admin = true. Usar después de auth:sanctum.
 * No hay endpoint para volverse admin: se marca en la base de datos.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Solo un administrador puede hacer esto',
            ], 403);
        }

        return $next($request);
    }
}
