<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class PreventNavigationDuringRandomQuiz
{
    private const QUIZ_STATE_KEY = 'quiz_state';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $state = Session::get(self::QUIZ_STATE_KEY);
        $routeName = (string) ($request->route()?->getName() ?? '');

        if (!is_array($state) || empty($state['lock_navigation'])) {
            return $next($request);
        }

        if (str_starts_with($routeName, 'quiz.')) {
            return $next($request);
        }

        if (in_array($routeName, ['questions.toggle_flag', 'logout'], true)) {
            return $next($request);
        }

        $examId = (int) ($state['exam_id'] ?? 0);
        if ($examId <= 0) {
            Session::forget(self::QUIZ_STATE_KEY);
            return $next($request);
        }

        return redirect()
            ->route('quiz.resume', ['exam' => $examId])
            ->with('error', '指定数ランダム出題モード中は、リロード以外の画面遷移はできません。');
    }
}
