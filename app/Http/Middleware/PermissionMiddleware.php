<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Middlewares\PermissionMiddleware as SpatiePermissionMiddleware;

class PermissionMiddleware extends SpatiePermissionMiddleware
{
    public function handle($request, Closure $next, $permission, $guard = null)
    {
        // Super admin bypasses all permission checks
        if (auth()->check() && auth()->user()->is_super_admin) {
            return $next($request);
        }

        return parent::handle($request, $next, $permission, $guard);
    }
}
