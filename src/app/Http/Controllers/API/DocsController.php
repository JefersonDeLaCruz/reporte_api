<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DocsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'version' => '1.0',
            'base_url' => config('app.url') . '/api',
            'auth_method' => 'Bearer Token (Laravel Sanctum)',
            'endpoints' => [
                // ==================== AUTENTICACIÓN ====================
                [
                    'group' => 'Authentication',
                    'method' => 'POST',
                    'path' => '/register',
                    'auth' => false,
                    'description' => 'Registrar nuevo usuario',
                    'body' => [
                        'name' => 'string (required)',
                        'email' => 'string, email (required, unique)',
                        'password' => 'string (required, min:8)',
                        'password_confirmation' => 'string (required, same as password)',
                    ],
                    'response' => [
                        'success' => true,
                        'message' => 'User registered successfully',
                        'token' => 'string (Bearer token)',
                        'user' => ['id', 'name', 'email', 'created_at'],
                    ],
                ],
                [
                    'group' => 'Authentication',
                    'method' => 'POST',
                    'path' => '/login',
                    'auth' => false,
                    'description' => 'Iniciar sesión con email/password',
                    'body' => [
                        'email' => 'string, email (required)',
                        'password' => 'string (required)',
                    ],
                    'response' => [
                        'success' => true,
                        'token' => 'string (Bearer token)',
                        'user' => ['id', 'name', 'email'],
                    ],
                ],
                [
                    'group' => 'Authentication',
                    'method' => 'POST',
                    'path' => '/auth/google',
                    'auth' => false,
                    'description' => 'Login/registro vía Google OAuth',
                    'body' => [
                        'id_token' => 'string (JWT de Google, required)',
                    ],
                    'response' => [
                        'success' => true,
                        'token' => 'string',
                        'user' => ['id', 'name', 'email', 'google_id', 'avatar_url'],
                    ],
                ],
                [
                    'group' => 'Authentication',
                    'method' => 'POST',
                    'path' => '/logout',
                    'auth' => true,
                    'description' => 'Cerrar sesión (revocar token)',
                    'body' => null,
                    'response' => ['success' => true, 'message' => 'Logged out successfully'],
                ],
                [
                    'group' => 'Authentication',
                    'method' => 'GET',
                    'path' => '/me',
                    'auth' => true,
                    'description' => 'Obtener datos del usuario autenticado',
                    'body' => null,
                    'response' => ['success' => true, 'user' => ['id', 'name', 'email', 'avatar_url', 'score', 'level']],
                ],
                [
                    'group' => 'Authentication',
                    'method' => 'GET',
                    'path' => '/users',
                    'auth' => true,
                    'description' => 'Listar todos los usuarios (admin)',
                    'body' => null,
                    'response' => ['success' => true, 'users' => '[]'],
                ],

                // ==================== PERFIL ====================
                [
                    'group' => 'Profile',
                    'method' => 'PUT',
                    'path' => '/me',
                    'auth' => true,
                    'description' => 'Actualizar nombre del usuario autenticado',
                    'body' => [
                        'name' => 'string (required, max:255)',
                    ],
                    'response' => [
                        'success' => true,
                        'message' => 'Perfil actualizado',
                        'user' => ['id', 'name', 'email', 'avatar_url', 'score', 'level'],
                    ],
                ],
                [
                    'group' => 'Profile',
                    'method' => 'POST',
                    'path' => '/me/avatar',
                    'auth' => true,
                    'description' => 'Subir o reemplazar la foto de perfil del usuario autenticado',
                    'content_type' => 'multipart/form-data',
                    'body' => [
                        'avatar' => 'file, image, max 5MB (required)',
                    ],
                    'response' => [
                        'success' => true,
                        'message' => 'Avatar actualizado',
                        'avatar_url' => 'string (URL completa)',
                    ],
                    'notes' => 'Si el usuario ya tenía un avatar subido localmente, se elimina del disco antes de guardar el nuevo. Avatares externos (ej: Google) no se eliminan.',
                ],
                [
                    'group' => 'Profile',
                    'method' => 'GET',
                    'path' => '/me/reports',
                    'auth' => true,
                    'description' => 'Listar los reportes creados por el usuario autenticado (paginado)',
                    'query' => [
                        'status' => 'pending|verified|resolved|archived (opcional)',
                        'per_page' => 'integer (default: 15)',
                    ],
                    'response' => ['success' => true, 'reports' => '{ current_page, data: [...], total, per_page }'],
                    'notes' => 'Misma forma que GET /reports, filtrado por user_id del usuario autenticado',
                ],
                [
                    'group' => 'Profile',
                    'method' => 'GET',
                    'path' => '/me/votes',
                    'auth' => true,
                    'description' => 'Historial de votos del usuario autenticado (paginado)',
                    'query' => [
                        'per_page' => 'integer (default: 15)',
                    ],
                    'response' => [
                        'success' => true,
                        'votes' => [
                            'current_page' => 'integer',
                            'data' => '[{ id, report_id, user_id, type, created_at, report: { id, description, status, photo_path, votes_confirm, votes_resolve, category } }]',
                            'total' => 'integer',
                            'per_page' => 'integer',
                        ],
                    ],
                ],

                // ==================== CATEGORÍAS ====================
                [
                    'group' => 'Categories',
                    'method' => 'GET',
                    'path' => '/categories',
                    'auth' => false,
                    'description' => 'Listar categorías disponibles',
                    'query' => ['active_only' => 'boolean (opcional)'],
                    'response' => ['success' => true, 'categories' => '[{ id, name, slug, icon, active, created_at }]'],
                ],
                [
                    'group' => 'Categories',
                    'method' => 'GET',
                    'path' => '/categories/{id}',
                    'auth' => false,
                    'description' => 'Obtener detalle de una categoría',
                    'response' => ['success' => true, 'category' => '{ id, name, slug, icon, active }'],
                ],
                [
                    'group' => 'Categories',
                    'method' => 'POST',
                    'path' => '/categories',
                    'auth' => true,
                    'description' => 'Crear nueva categoría',
                    'body' => [
                        'name' => 'string (required)',
                        'slug' => 'string (required, unique)',
                        'icon' => 'string (required)',
                        'active' => 'boolean (optional, default: true)',
                    ],
                    'response' => ['success' => true, 'message' => 'Category created', 'category' => '{}'],
                ],
                [
                    'group' => 'Categories',
                    'method' => 'PUT',
                    'path' => '/categories/{id}',
                    'auth' => true,
                    'description' => 'Actualizar categoría',
                    'body' => ['name?', 'slug?', 'icon?', 'active?'],
                    'response' => ['success' => true, 'message' => 'Category updated', 'category' => '{}'],
                ],
                [
                    'group' => 'Categories',
                    'method' => 'DELETE',
                    'path' => '/categories/{id}',
                    'auth' => true,
                    'description' => 'Eliminar categoría',
                    'body' => null,
                    'response' => ['success' => true, 'message' => 'Category deleted'],
                ],

                // ==================== REPORTES ====================
                [
                    'group' => 'Reports',
                    'method' => 'GET',
                    'path' => '/reports',
                    'auth' => false,
                    'description' => 'Listar reportes (paginado)',
                    'query' => [
                        'status' => 'pending|verified|resolved|archived (opcional)',
                        'category_id' => 'integer (opcional)',
                        'per_page' => 'integer (default: 15)',
                    ],
                    'response' => ['success' => true, 'reports' => '{ current_page, data: [...], total, per_page }'],
                ],
                [
                    'group' => 'Reports',
                    'method' => 'GET',
                    'path' => '/reports/{id}',
                    'auth' => 'optional',
                    'description' => 'Obtener detalle de reporte con información de votos del usuario',
                    'notes' => 'Autentica automáticamente si se envía Bearer token',
                    'response' => [
                        'success' => true,
                        'report' => [
                            'id' => 'integer',
                            'latitude' => 'decimal',
                            'longitude' => 'decimal',
                            'status' => 'pending|verified|resolved|archived',
                            'description' => 'string',
                            'category' => '{ id, name, slug, icon }',
                            'user' => '{ id, name, avatar_url }',
                            'votes' => '{ confirm: int, resolve: int }',
                            'user_vote' => 'null|"confirm"|"resolve" (voto del usuario autenticado)',
                            'user_voted_at' => 'ISO 8601 timestamp (cuando votó el usuario)',
                            'created_at' => 'ISO 8601 timestamp',
                            'updated_at' => 'ISO 8601 timestamp',
                        ],
                    ],
                ],
                [
                    'group' => 'Reports',
                    'method' => 'POST',
                    'path' => '/reports',
                    'auth' => true,
                    'description' => 'Crear nuevo reporte',
                    'body' => [
                        'category_id' => 'integer (required, exists:categories)',
                        'latitude' => 'numeric, -90 to 90 (required)',
                        'longitude' => 'numeric, -180 to 180 (required)',
                        'description' => 'string, max 500 (required)',
                        'photo' => 'file, image, max 5MB (optional)',
                    ],
                    'response' => ['success' => true, 'message' => 'Reporte creado', 'report' => '{}'],
                ],
                [
                    'group' => 'Reports',
                    'method' => 'PUT',
                    'path' => '/reports/{id}',
                    'auth' => true,
                    'description' => 'Actualizar reporte (solo el dueño)',
                    'body' => ['category_id?', 'description?', 'photo?'],
                    'response' => ['success' => true, 'message' => 'Reporte actualizado', 'report' => '{}'],
                ],
                [
                    'group' => 'Reports',
                    'method' => 'DELETE',
                    'path' => '/reports/{id}',
                    'auth' => true,
                    'description' => 'Eliminar reporte (solo el dueño, borra foto también)',
                    'body' => null,
                    'response' => ['success' => true, 'message' => 'Reporte eliminado'],
                ],
                [
                    'group' => 'Reports',
                    'method' => 'PATCH',
                    'path' => '/reports/{id}/status',
                    'auth' => true,
                    'description' => 'Cambiar estado del reporte (pending → verified → resolved → archived)',
                    'body' => ['status' => 'pending|verified|resolved|archived (required)'],
                    'response' => ['success' => true, 'message' => 'Estado actualizado', 'report' => '{}'],
                    'notes' => 'Actualiza status_changed_at y el timestamp específico (verified_at, resolved_at, archived_at)',
                ],

                // ==================== VOTOS ====================
                [
                    'group' => 'Voting',
                    'method' => 'POST',
                    'path' => '/reports/{id}/votes',
                    'auth' => true,
                    'description' => 'Votar un reporte (confirm o resolve)',
                    'notes' => 'Usuario debe estar a <500m del reporte (validación Haversine). Retorna 409 si ya votó ese tipo',
                    'body' => [
                        'type' => 'confirm|resolve (required)',
                        'latitude' => 'numeric (required, user location)',
                        'longitude' => 'numeric (required, user location)',
                    ],
                    'response' => [
                        '201 Created' => [
                            'success' => true,
                            'message' => 'Voto registrado',
                            'data' => [
                                'type' => 'confirm|resolve',
                                'user_id' => 'integer',
                                'report_id' => 'integer',
                                'votes_confirm' => 'integer (total updated)',
                                'votes_resolve' => 'integer (total updated)',
                                'created_at' => 'ISO 8601 timestamp',
                            ],
                        ],
                        '409 Conflict' => 'Ya votaste este reporte con ese tipo',
                        '422 Unprocessable Entity' => 'Usuario fuera de 500m de distancia',
                    ],
                ],
                [
                    'group' => 'Voting',
                    'method' => 'DELETE',
                    'path' => '/reports/{id}/votes/{type}',
                    'auth' => true,
                    'description' => 'Retirar voto propio (confirm o resolve)',
                    'body' => null,
                    'response' => ['success' => true, 'message' => 'Voto retirado', 'report' => '{}'],
                    'notes' => 'Decrementa los contadores votes_confirm o votes_resolve',
                ],
            ],
        ]);
    }
}
