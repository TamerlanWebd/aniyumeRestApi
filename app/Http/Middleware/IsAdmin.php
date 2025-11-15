<?php

namespace App\Http\Middleware;

use Closure;

class IsAdmin
{
    public function handle($request, Closure $next)
    {
        if (!auth()->user() || !auth()->user()->isAdmin()) {
            abort(403, 'Admin access only');
        }
        return $next($request);
    }
}
Route::middleware(['auth:sanctum'])->get('/admin-check', function (Request $request) {
    return response()->json([
        'is_admin' => $request->user()->isAdmin()
    ]);
});

