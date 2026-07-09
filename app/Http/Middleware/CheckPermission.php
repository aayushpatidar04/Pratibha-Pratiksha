<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Usage in routes: ->middleware('permission:residents,create')
     * module = the key from config/modules.php, action = view|create|edit|delete
     */
    public function handle(Request $request, Closure $next, string $module, string $action = 'view'): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission($module, $action)) {
            abort(403, "You don't have permission to {$action} {$module}.");
        }

        return $next($request);
    }
}