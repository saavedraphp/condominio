<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson()) {

            if ($request->routeIs('user.*') || Str::startsWith($request->path(), 'user/')) {
                return route('user.login');
            }

            if( $request->routeIs('admin.*') || Str::startsWith($request->path(), 'admin/')) {
                return route('admin.login');
            }
        }

        return null;
    }
}
