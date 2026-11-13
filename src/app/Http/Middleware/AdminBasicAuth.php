<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege el panel /admin con HTTP Basic contra ADMIN_USER / ADMIN_PASSWORD.
 * No hay roles en la tabla users, así que el acceso no depende de una cuenta de la app.
 * Sin credenciales configuradas el panel no existe (404).
 */
class AdminBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = (string) config('services.admin.user');
        $password = (string) config('services.admin.password');

        if ($user === '' || $password === '') {
            abort(404);
        }

        $valid = hash_equals($user, (string) $request->getUser())
            & hash_equals($password, (string) $request->getPassword());

        if (!$valid) {
            return response('Credenciales requeridas.', 401, [
                'WWW-Authenticate' => 'Basic realm="Admin", charset="UTF-8"',
            ]);
        }

        return $next($request);
    }
}
