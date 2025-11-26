<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Assuming user has a 'role' attribute. 
        // If using Firestore User model, ensure it's hydrated correctly.
        // For this prototype, we might check a claim or a DB field.
        
        $userRole = $request->user()->role ?? 'user'; // Default to user
        
        // Hierarchy: admin > moderator > user
        $roles = [
            'user' => 1,
            'moderator' => 2,
            'admin' => 3,
        ];
        
        $userLevel = $roles[$userRole] ?? 0;
        $requiredLevel = $roles[$role] ?? 100;

        if ($userLevel < $requiredLevel) {
            return response()->json(['message' => 'Forbidden. Insufficient permissions.'], 403);
        }

        return $next($request);
    }
}
