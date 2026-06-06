<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
            ]);

            DB::beginTransaction();

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $token = $user->createToken('api-token')->plainTextToken;
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'token' => $token,
                'user' => $user,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during registration',
                'error' => config('app.debug') ? $th->getMessage() : null,
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas',
            ], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => 'Bearer ' . $token,
            'user' => $user,
        ]);
    }

    public function googleLogin(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        try {
            $client = new GoogleClient();
            $client->setClientId(config('services.google.client_id'));

            $payload = $client->verifyIdToken($request->id_token);

            if (!$payload) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de Google inválido',
                ], 401);
            }

            DB::beginTransaction();

            $user = User::where('google_id', $payload['sub'])
                ->orWhere('email', $payload['email'])
                ->first();

            if ($user) {
                // Update google_id and avatar if missing
                $user->update([
                    'google_id' => $payload['sub'],
                    'avatar_url' => $user->avatar_url ?? ($payload['picture'] ?? null),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            } else {
                $user = User::create([
                    'name' => $payload['name'],
                    'email' => $payload['email'],
                    'google_id' => $payload['sub'],
                    'avatar_url' => $payload['picture'] ?? null,
                    'password' => Hash::make(str()->random(32)),
                    'email_verified_at' => now(),
                ]);
            }

            $token = $user->createToken('api-token')->plainTextToken;
            DB::commit();

            return response()->json([
                'success' => true,
                'token' => 'Bearer ' . $token,
                'user' => $user,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al autenticar con Google',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }

    public function getAllUser()
    {
        $users = User::select('id', 'name', 'email', 'avatar_url', 'score', 'level', 'created_at')->get();

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }
}
