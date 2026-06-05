<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (Auth::user()->isAdmin())
                <div class="mb-6 grid gap-3 sm:grid-cols-3">
                    <a href="{{ route('exams.index') }}" class="rounded-lg bg-blue-600 px-4 py-3 text-center font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        試験管理
                    </a>
                    <a href="{{ route('questions.index') }}" class="rounded-lg bg-emerald-600 px-4 py-3 text-center font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                        問題管理
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="rounded-lg bg-slate-700 px-4 py-3 text-center font-semibold text-white shadow-sm transition hover:bg-slate-800">
                        ユーザー管理
                    </a>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-bold text-gray-900">アクティブユーザー（最終ログイン半年以内）</h3>
                        <p class="mt-1 text-sm text-gray-600">最終ログイン日時が直近6か月以内のユーザーを表示しています。</p>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full border border-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="border-b px-3 py-2 text-left text-sm">名前</th>
                                        <th class="border-b px-3 py-2 text-left text-sm">メールアドレス</th>
                                        <th class="border-b px-3 py-2 text-left text-sm">ロール</th>
                                        <th class="border-b px-3 py-2 text-left text-sm">最終ログイン日時</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($activeUsers as $activeUser)
                                        <tr>
                                            <td class="border-b px-3 py-2 text-sm">{{ $activeUser->name }}</td>
                                            <td class="border-b px-3 py-2 text-sm">{{ $activeUser->email }}</td>
                                            <td class="border-b px-3 py-2 text-sm">{{ $activeUser->role }}</td>
                                            <td class="border-b px-3 py-2 text-sm">{{ $activeUser->last_login_at?->format('Y-m-d H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-3 py-4 text-center text-sm text-gray-500">該当するユーザーがいません。</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $activeUsers?->links() }}
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        {{ __("You're logged in!") }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
