<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <-- ДОБАВЬ ЭТУ СТРОКУ
use App\Models\User;
use Kreait\Firebase\Factory;

class AuthController extends Controller
{
    public function googleAuth(Request $request)
    {
        try {
            \Log::info('🔐 Начало Google OAuth');
            
            $request->validate(['token' => 'required|string']);

            $client = new \Google\Client(['client_id' => '512857196956-ajqmk34it9bp44bsrnf86m7fr2h8g9r0.apps.googleusercontent.com']);  // Specify the CLIENT_ID of the app that accesses the backend
            
            \Log::info('🔍 Проверяем token через Google Client...');
            $payload = $client->verifyIdToken($request->token);
            
            if ($payload) {
                $uid = $payload['sub'];
                $email = $payload['email'];
                $name = $payload['name'];
                $avatar = $payload['picture'];
                
                \Log::info('✅ Token верифицирован', ['uid' => $uid, 'email' => $email]);

                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'avatar' => $avatar,
                        'firebase_uid' => $uid, // We can keep this field name or rename it to google_uid if preferred
                    ]
                );
            } else {
                throw new \Exception('Invalid ID token');
            }

            \Log::info('✅ Пользователь создан/обновлен', ['user_id' => $user->id]);

            // Create Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;

            \Log::info('✅ Token created for user', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => 'Authenticated successfully',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'is_admin' => $user->isAdmin()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Google Auth error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Проверка админ прав
     */
    public function checkAdmin(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'is_admin' => $user && $user->isAdmin(),
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'is_admin' => $user->isAdmin()
            ] : null
        ]);
    }

    /**
     * Выход (logout)
     */
    public function logout(Request $request)
    {
        try {
            // Удаляем текущий токен
            $request->user()->currentAccessToken()->delete();
            
            \Log::info('✅ Пользователь вышел', [
                'user_id' => $request->user()->id
            ]);

            // Удаляем cookie
            $cookie = cookie()->forget('auth_token');

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ])->withCookie($cookie);

        } catch (\Exception $e) {
            \Log::error('❌ Logout error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed'
            ], 500);
        }
    }
}
