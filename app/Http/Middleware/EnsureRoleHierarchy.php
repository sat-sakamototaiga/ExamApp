<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleHierarchy
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $requiredRole): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRoleLevel($requiredRole)) {
            abort(403, 'この操作を実行する権限がありません。');
        }

        return $next($request);
    }
}
