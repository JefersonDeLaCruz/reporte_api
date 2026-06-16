<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        // Always return success to avoid email enumeration
        if (!$user) {
            return response()->json(['success' => true, 'message' => 'Si el correo existe, recibirás un enlace.']);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        Http::withHeaders(['api-key' => env('BREVO_API_KEY')])
            ->post('https://api.brevo.com/v3/smtp/email', [
                'sender'     => ['name' => config('app.name'), 'email' => config('mail.from.address')],
                'to'         => [['email' => $request->email]],
                'subject'    => 'Recuperación de contraseña',
                'htmlContent' => view('emails.password-reset', ['token' => $token, 'email' => $request->email])->render(),
            ]);

        return response()->json(['success' => true, 'message' => 'Si el correo existe, recibirás un enlace.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'token'                 => 'required|string',
            'password'              => 'required|string|min:6|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Token inválido o expirado.'], 422);
        }

        // Token expires after 60 minutes
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['success' => false, 'message' => 'Token expirado.'], 422);
        }

        if (!Hash::check($request->token, $record->token)) {
            return response()->json(['success' => false, 'message' => 'Token inválido.'], 422);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);
    }
}
