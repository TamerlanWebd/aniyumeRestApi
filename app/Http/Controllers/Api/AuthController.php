<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Kreait\Firebase\Factory;

class AuthController extends Controller
{
    /**
     * Google OAuth авторизация через Firebase
     */
    public function googleAuth(Request $request)
    {
        try {
            // Логируем начало процесса
            \Log::info('🔐 Начало Google OAuth');
            
            // Валидация idToken
            $request->validate([
                'idToken' => 'required|string'
            ]);

            // Инициализация Firebase Admin SDK
            $firebaseCredentialsPath = base_path(env('FIREBASE_CREDENTIALS'));
            
            if (!file_exists($firebaseCredentialsPath)) {
                \Log::error('❌ Firebase credentials файл не найден');
                return response()->json([
                    'success' => false,
                    'message' => 'Firebase configuration error'
                ], 500);
            }

            $factory = (new Factory)->withServiceAccount($firebaseCredentialsPath);
            $firebaseAuth = $factory->createAuth();

            // Верификация idToken через Firebase
            \Log::info('🔍 Проверяем idToken...');
            $verifiedIdToken = $firebaseAuth->verifyIdToken($request->idToken);
            $uid = $verifiedIdToken->claims()->get('sub');
            $email = $verifiedIdToken->claims()->get('email');
            $name = $verifiedIdToken->claims()->get('name');
            $avatar = $verifiedIdToken->claims()->get('picture');

            \Log::info('✅ Token верифицирован', [
                'uid' => $uid,
                'email' => $email,
                'name' => $name
            ]);

            // Создаём или обновляем пользователя
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'avatar' => $avatar,
                    'firebase_uid' => $uid,
                    'is_admin' => false // По умолчанию не админ
                ]
            );

            \Log::info('✅ Пользователь создан/обновлен', [
                'user_id' => $user->id,
                'is_admin' => $user->isAdmin()
            ]);

            // Удаляем старые токены этого пользователя
            $user->tokens()->delete();

            // Создаём новый Sanctum токен
            $token = $user->createToken('api')->plainTextToken;
            \Log::info('✅ Sanctum токен создан');

            // Возвращаем токен в httpOnly cookie
            $cookie = cookie(
                'auth_token',          // название cookie
                $token,                // значение (токен)
                60 * 24 * 7,          // 7 дней в минутах
                '/',                   // путь
                'localhost',           // домен (ВАЖНО: localhost для фронта)
                false,                 // secure (true только для HTTPS)
                true,                  // httpOnly (максимальная защита!)
                false,                 // raw
                'Lax'                  // sameSite
            );

            return response()->json([
                'success' => true,
                'message' => 'Authenticated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'is_admin' => $user->isAdmin()
                ]
            ])->cookie($cookie);

        } catch (\Kreait\Firebase\Exception\Auth\FailedToVerifyToken $e) {
            \Log::error('❌ Firebase token verification failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid Firebase token'
            ], 401);

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
