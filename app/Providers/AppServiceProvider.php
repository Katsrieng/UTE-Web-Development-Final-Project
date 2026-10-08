<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        foreach (\App\Support\RbacCatalog::permissions() as $permission) {
            \Illuminate\Support\Facades\Gate::define($permission, fn (\App\Models\User $user) => $user->hasPermission($permission));
        }
        \Illuminate\Support\Facades\Blade::if('staffroute', function (string $route): bool {
            $permission = \App\Support\RbacCatalog::routePermission($route);
            return !$permission || (auth()->user() && \App\Support\RbacCatalog::allowsRoute(auth()->user(), $route));
        });
    }
}
