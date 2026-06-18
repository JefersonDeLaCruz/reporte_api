<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ReportVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Actualiza los datos del perfil del usuario autenticado (nombre).
     */
    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $user = $request->user();
            $user->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado',
                'user' => $user->fresh(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Sube o reemplaza la foto de perfil del usuario autenticado.
     */
    public function uploadAvatar(Request $request)
    {
        try {
            $request->validate([
                'avatar' => 'required|image|max:5120',
            ]);

            $user = $request->user();

            if ($user->avatar_url) {
                $oldPath = $this->avatarPathFromUrl($user->avatar_url);

                if ($oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_url = Storage::disk('public')->url($path);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Avatar actualizado',
                'avatar_url' => $user->avatar_url,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Lista los reportes creados por el usuario autenticado (paginado).
     */
    public function reports(Request $request)
    {
        $reports = Report::query()
            ->where('user_id', $request->user()->id)
            ->with(['user:id,name,avatar_url', 'category'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'reports' => $reports,
        ]);
    }

    /**
     * Lista el historial de votos del usuario autenticado (paginado).
     */
    public function votes(Request $request)
    {
        $votes = ReportVote::query()
            ->where('user_id', $request->user()->id)
            ->with(['report' => function ($query) {
                $query->select('id', 'category_id', 'description', 'status', 'photo_path', 'votes_confirm', 'votes_resolve')
                    ->with('category:id,name,slug,icon');
            }])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'votes' => $votes,
        ]);
    }

    /**
     * Actualiza el token de FCM del usuario autenticado para notificaciones push.
     */
    public function updateFcmToken(Request $request)
    {
        try {
            $data = $request->validate([
                'fcm_token' => 'required|string|max:500',
            ]);

            $user = $request->user();
            $user->update(['fcm_token' => $data['fcm_token']]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token actualizado',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Extrae la ruta relativa (en el disco "public") de una URL de avatar
     * generada localmente, o null si la URL es externa (ej: foto de Google).
     */
    private function avatarPathFromUrl(string $url): ?string
    {
        $prefix = Storage::disk('public')->url('');

        if (!str_starts_with($url, $prefix)) {
            return null;
        }

        return ltrim(substr($url, strlen($prefix)), '/');
    }
}
