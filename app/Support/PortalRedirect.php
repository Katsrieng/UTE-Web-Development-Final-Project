<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PortalRedirect
{
    public static function loginFor(Request $request): string
    {
        foreach ($request->route()?->gatherMiddleware() ?? [] as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'role:') && array_intersect(explode(',', substr($middleware, 5)), ['admin', 'manager', 'staff'])) {
                return route('staff.login');
            }
        }

        return route('login');
    }

    public static function afterLogin(Request $request, User $user)
    {
        $intended = $request->session()->get('url.intended');
        if (! self::compatible($intended, $user)) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended(route($user->homeRoute()));
    }

    private static function compatible(mixed $url, User $user): bool
    {
        if (! is_string($url) || preg_match('/[\\\\\x00-\x20]/', $url) || str_starts_with($url, '//')) {
            return false;
        }
        $parts = parse_url($url);
        $origin = parse_url(route('welcome'));
        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }
        if (isset($parts['scheme']) || isset($parts['host'])) {
            foreach (['scheme', 'host', 'port'] as $key) {
                if (($parts[$key] ?? null) !== ($origin[$key] ?? null)) {
                    return false;
                }
            }
        } elseif (! str_starts_with($url, '/')) {
            return false;
        }
        try {
            $route = Route::getRoutes()->match(Request::create($url, 'GET'));
            if (in_array($route->getName(), ['login', 'staff.login', 'register', 'home'], true)) {
                return false;
            }
            foreach ($route->gatherMiddleware() as $middleware) {
                if (is_string($middleware) && str_starts_with($middleware, 'role:') && ! $user->hasRole(...explode(',', substr($middleware, 5)))) {
                    return false;
                }
            }

            $permission = RbacCatalog::routePermission($route->getName());
            return !$permission || RbacCatalog::allowsRoute($user, $route->getName());
        } catch (HttpException $exception) {
            return false;
        }
    }
}
