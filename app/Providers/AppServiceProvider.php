<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        \App\Models\Anime::observe(\App\Observers\AuditObserver::class);
        \App\Models\Tag::observe(\App\Observers\AuditObserver::class);
        \App\Models\User::observe(\App\Observers\AuditObserver::class);

        \Illuminate\Support\Facades\RateLimiter::for('guest', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by($request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('auth', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by($request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('user', function (\Illuminate\Http\Request $request) {
            return $request->user()
                ? \Illuminate\Cache\RateLimiting\Limit::perMinute(100)->by($request->user()->id)
                : \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by($request->ip());
        });

        \Illuminate\Support\Facades\RateLimiter::for('admin', function (\Illuminate\Http\Request $request) {
            return $request->user() && method_exists($request->user(), 'isAdmin') && $request->user()->isAdmin()
                ? \Illuminate\Cache\RateLimiting\Limit::perMinute(1000)->by($request->user()->id)
                : \Illuminate\Cache\RateLimiting\Limit::perMinute(100)->by($request->user()->id ?? $request->ip());
        });
    }
}
