<x-app-layout>
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold text-center text-emerald-700">試験リザルト</h1>
        <h2 class="mt-2 text-3xl font-bold text-center text-emerald-700">{{ $exam->name }}</h2>

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-4">
                <p class="text-sm text-emerald-800">正解数</p>
                <p class="mt-1 text-2xl font-bold text-emerald-900">{{ $score }} / {{ $question_count }}</p>
            </div>
            <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-4">
                <p class="text-sm text-sky-800">正答率</p>
                <p class="mt-1 text-2xl font-bold text-sky-900">{{ $accuracy_rate }}%</p>
            </div>
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-4 sm:col-span-2">
                <p class="text-sm text-amber-800">獲得ポイント</p>
                <p class="mt-1 text-2xl font-bold text-amber-900">{{ (int) $points_earned }} pt</p>
                @if ((int) $bonus_points > 0)
                    <p class="mt-1 text-sm text-amber-800">内訳: 全問正解ボーナス +{{ (int) $bonus_points }} pt</p>
                @endif
            </div>
        </div>

        <div class="mt-8 rounded-lg border border-gray-200 bg-gray-50 p-5">
            <h3 class="text-lg font-bold text-gray-900">問題文と成否</h3>

            <div class="mt-4 space-y-3">
                @forelse ($question_outcomes as $outcome)
                    <div class="rounded-md border border-gray-200 bg-white px-4 py-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <p class="text-sm text-gray-800">
                                問題{{ $loop->iteration }}: {{ $outcome['question_text'] }}
                            </p>
                            @if (!empty($outcome['is_correct']))
                                <span class="shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">正解</span>
                            @else
                                <span class="shrink-0 rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800">不正解</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-md border border-gray-200 bg-white px-4 py-4 text-sm text-gray-500">
                        表示できる問題履歴がありません。
                    </div>
                @endforelse
            </div>
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('quiz.select_exam') }}" class="rounded-full bg-blue-600 px-6 py-2 font-bold text-white shadow-md transition hover:bg-blue-700">
                他の試験を選択
            </a>
            <a href="{{ route('dashboard') }}" class="rounded-full bg-gray-200 px-6 py-2 font-bold text-gray-800 shadow-md transition hover:bg-gray-300">
                ダッシュボードへ戻る
            </a>
        </div>
    </div>
</x-app-layout>
