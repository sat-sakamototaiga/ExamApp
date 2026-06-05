<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $activeUsers = null;

        if ($user?->isAdmin()) {
            $activeUsers = User::query()
                ->where('last_login_at', '>=', now()->subMonths(6))
                ->orderByDesc('last_login_at')
                ->orderBy('id')
                ->paginate(20, ['id', 'name', 'email', 'role', 'last_login_at']);
        }

        return view('dashboard', [
            'activeUsers' => $activeUsers,
        ]);
    }
}