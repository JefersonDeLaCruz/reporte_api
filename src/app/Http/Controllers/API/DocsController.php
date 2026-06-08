<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint de auto-documentación. Lista todos los endpoints disponibles
 * y cómo usarlos. Actualizar manualmente cada vez que se crea, modifica
 * o elimina un endpoint en routes/api.php.
 */
class DocsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'endpoints' => [
                // Auth
                ['method' => 'POST', 'path' => '/register', 'auth' => false, 'body' => ['name', 'email', 'password', 'password_confirmation'], 'description' => 'Registrar usuario nuevo'],
                ['method' => 'POST', 'path' => '/login', 'auth' => false, 'body' => ['email', 'password'], 'description' => 'Login con email/password, retorna token'],
                ['method' => 'POST', 'path' => '/auth/google', 'auth' => false, 'body' => ['id_token'], 'description' => 'Login/registro vía Google OAuth'],
                ['method' => 'POST', 'path' => '/logout', 'auth' => true, 'body' => null, 'description' => 'Revoca el token actual'],
                ['method' => 'GET', 'path' => '/me', 'auth' => true, 'body' => null, 'description' => 'Datos del usuario autenticado'],
                ['method' => 'GET', 'path' => '/users', 'auth' => true, 'body' => null, 'description' => 'Lista todos los usuarios'],

                // Categories
                ['method' => 'GET', 'path' => '/categories', 'auth' => false, 'body' => null, 'query' => ['active_only' => 'bool'], 'description' => 'Lista categorías'],
                ['method' => 'GET', 'path' => '/categories/{id}', 'auth' => false, 'body' => null, 'description' => 'Detalle de categoría'],
                ['method' => 'POST', 'path' => '/categories', 'auth' => true, 'body' => ['name', 'slug', 'icon', 'active?'], 'description' => 'Crear categoría'],
                ['method' => 'PUT', 'path' => '/categories/{id}', 'auth' => true, 'body' => ['name?', 'slug?', 'icon?', 'active?'], 'description' => 'Actualizar categoría'],
                ['method' => 'DELETE', 'path' => '/categories/{id}', 'auth' => true, 'body' => null, 'description' => 'Eliminar categoría'],

                // Reports
                ['method' => 'GET', 'path' => '/reports', 'auth' => false, 'body' => null, 'query' => ['status', 'category_id', 'per_page'], 'description' => 'Lista reportes paginados (con user y category)'],
                ['method' => 'GET', 'path' => '/reports/{id}', 'auth' => false, 'body' => null, 'description' => 'Detalle de reporte'],
                ['method' => 'POST', 'path' => '/reports', 'auth' => true, 'body' => ['category_id', 'latitude', 'longitude', 'description', 'photo? (multipart, image)'], 'description' => 'Crear reporte (status inicial: pending)'],
                ['method' => 'PUT', 'path' => '/reports/{id}', 'auth' => true, 'body' => ['category_id?', 'description?', 'photo? (multipart, image)'], 'description' => 'Actualizar reporte (solo dueño)'],
                ['method' => 'DELETE', 'path' => '/reports/{id}', 'auth' => true, 'body' => null, 'description' => 'Eliminar reporte (solo dueño, borra foto)'],
                ['method' => 'PATCH', 'path' => '/reports/{id}/status', 'auth' => true, 'body' => ['status: pending|verified|resolved|archived'], 'description' => 'Cambiar estado del reporte (setea timestamp correspondiente)'],

                // Votes
                ['method' => 'POST', 'path' => '/reports/{id}/votes', 'auth' => true, 'body' => ['type: confirm|resolve', 'latitude', 'longitude'], 'description' => 'Votar reporte; requiere estar a <500m (Haversine). 409 si ya votó ese tipo'],
                ['method' => 'DELETE', 'path' => '/reports/{id}/votes/{type}', 'auth' => true, 'body' => null, 'description' => 'Retirar voto propio (confirm|resolve)'],
            ],
        ]);
    }
}
