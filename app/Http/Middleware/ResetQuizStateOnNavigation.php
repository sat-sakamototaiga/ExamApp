<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class ResetQuizStateOnNavigation
{
    private const QUIZ_STATE_KEY = 'quiz_state';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $state = Session::get(self::QUIZ_STATE_KEY);
        $routeName = (string) ($request->route()?->getName() ?? '');

        if (is_array($state)
            && !str_starts_with($routeName, 'quiz.')
            && $routeName !== 'questions.toggle_flag') {
            Session::forget(self::QUIZ_STATE_KEY);
        }

        return $next($request);
    }
}
