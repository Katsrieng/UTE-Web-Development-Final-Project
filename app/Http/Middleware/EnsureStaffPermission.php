<?php
namespace App\Http\Middleware;
use App\Support\RbacCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class EnsureStaffPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $permission = RbacCatalog::routePermission($request->route()?->getName());
        abort_unless($permission && $request->user() && RbacCatalog::allowsRoute($request->user(), $request->route()?->getName()), 403, 'You do not have permission to perform this action.');
        return $next($request);
    }
}
