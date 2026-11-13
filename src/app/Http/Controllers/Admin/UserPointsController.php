<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class UserPointsController extends Controller
{
    /** Lista de usuarios con búsqueda por nombre o correo. */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderByDesc('score')
            ->orderBy('id')
            ->paginate(25, ['id', 'name', 'email', 'score', 'coins', 'level', 'created_at'])
            ->withQueryString();

        return view('admin.users', ['users' => $users, 'search' => $search]);
    }

    /**
     * Suma puntos a mano. Usa User::addScore, igual que los premios por votos:
     * sube score y coins y recalcula el nivel.
     */
    public function addPoints(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'points' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        $user->addScore($data['points']);

        Log::info('admin.add_points', [
            'user_id' => $user->id,
            'points' => $data['points'],
            'score' => $user->score,
            'ip' => $request->ip(),
        ]);

        return back()->with('status', "Se sumaron {$data['points']} puntos a {$user->name} (score: {$user->score}, nivel: {$user->level}).");
    }
}
