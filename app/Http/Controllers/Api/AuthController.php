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
            
            $request->validate(['idToken' => 'required|string']);

            $firebaseCredentialsPath = base_path(env('FIREBASE_CREDENTIALS'));
            
            if (!file_exists($firebaseCredentialsPath)) {
                \Log::error('❌ Firebase credentials файл не найден');
                return response()->json(['success' => false, 'message' => 'Firebase configuration error'], 500);
            }

            $factory = (new Factory)->withServiceAccount($firebaseCredentialsPath);
            $firebaseAuth = $factory->createAuth();

            \Log::info('🔍 Проверяем idToken...');
            $verifiedIdToken = $firebaseAuth->verifyIdToken($request->idToken);
            $uid = $verifiedIdToken->claims()->get('sub');
            $email = $verifiedIdToken->claims()->get('email');
            $name = $verifiedIdToken->claims()->get('name');
            $avatar = $verifiedIdToken->claims()->get('picture');

            \Log::info('✅ Token верифицирован', ['uid' => $uid, 'email' => $email]);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'avatar' => $avatar,
                    'firebase_uid' => $uid,
                ]
            );

            \Log::info('✅ Пользователь создан/обновлен', ['user_id' => $user->id]);

            // =================================================================
            // ЗОЛОТОЕ РЕШЕНИЕ: ЛОГИНИМ ПОЛЬЗОВАТЕЛЯ В СТАНДАРТНУЮ СЕССИЮ LARAVEL
            // =================================================================
            Auth::login($user);
            $request->session()->regenerate(); // <-- Это создает сессию и отправляет правильную cookie
            // =================================================================

            \Log::info('✅ Пользователь залогинен в сессию Laravel');

            // Теперь нам не нужно вручную создавать cookie. Laravel сделает все сам.
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
