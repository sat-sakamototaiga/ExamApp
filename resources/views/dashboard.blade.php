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
            @elseif (Auth::user()->isTeacher() && $teacherDashboard)
                <div
                    x-data="{ showResetModal: false, showAutoResetModal: false, showDonePopup: {{ session('completed_action') === 'points_reset' ? 'true' : 'false' }} }"
                    x-init="if (showDonePopup) { setTimeout(() => { showDonePopup = false }, 3000) }"
                    @keydown.escape.window="showResetModal = false; showAutoResetModal = false"
                >
                    {{-- 要件: 未フィードバック警告はダッシュボード上部に文言表示のみ。 --}}
                    @if ($teacherDashboard['studentsWithoutRecentFeedback']->isNotEmpty())
                        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-800">
                            <p class="font-semibold">未フィードバックの担当生徒がいます</p>
                            <p class="mt-1 text-sm">1か月以上フィードバックがない生徒: {{ $teacherDashboard['studentsWithoutRecentFeedback']->count() }}名</p>
                        </div>
                    @endif

                    @if (session('success') && session('completed_action') !== 'points_reset')
                        <div class="mb-6 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-gray-700">ポイント設定</h3>
                    </div>
                    <div class="mb-6 grid gap-3 sm:grid-cols-2">
                        <button type="button" @click="showResetModal = true" class="rounded-lg bg-indigo-600 px-4 py-3 text-center font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                            ポイントリセット
                        </button>
                        <button type="button" @click="showAutoResetModal = true" class="rounded-lg bg-sky-600 px-4 py-3 text-center font-semibold text-white shadow-sm transition hover:bg-sky-700">
                            オートリセット設定
                        </button>
                    </div>

                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-gray-700">管理・確認</h3>
                    </div>
                    <div class="mb-6 grid gap-3 sm:grid-cols-2">
                        <a href="{{ route('teacher.students.progress') }}" class="rounded-lg bg-blue-600 px-4 py-3 text-center font-semibold text-white shadow-sm transition hover:bg-blue-700">
                            担当生徒の進捗
                        </a>
                        <a href="{{ route('questions.index') }}" class="rounded-lg bg-emerald-600 px-4 py-3 text-center font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                            問題管理
                        </a>
                    </div>

                    {{-- 要件: リセットは警告モーダルの実行ボタンからのみ送信する。 --}}
                    <div x-show="showResetModal" class="fixed inset-0 z-40 bg-black/40" @click="showResetModal = false" style="display: none;"></div>
                    <div x-show="showResetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @click.stop>
                            <h3 class="text-lg font-bold text-gray-900">ポイントリセット確認</h3>
                            <p class="mt-2 text-sm text-gray-700">担当生徒について、この教科で獲得したポイントを0にリセットします。この操作は取り消せません。</p>

                            <form action="{{ route('teacher.students.points.reset') }}" method="POST" class="mt-5 flex justify-end gap-3">
                                @csrf
                                <button type="button" @click="showResetModal = false" class="rounded border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">キャンセル</button>
                                <button type="submit" class="rounded bg-red-600 px-4 py-2 text-white hover:bg-red-700">削除を実行</button>
                            </form>
                        </div>
                    </div>

                    {{-- 要件: オートリセット設定はモーダルで編集する。 --}}
                    <div x-show="showAutoResetModal" class="fixed inset-0 z-40 bg-black/40" @click="showAutoResetModal = false" style="display: none;"></div>
                    <div x-show="showAutoResetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @click.stop>
                            <h3 class="text-lg font-bold text-gray-900">オートリセット設定</h3>

                            <form action="{{ route('teacher.students.points.reset-interval') }}" method="POST" class="mt-4 space-y-3">
                                @csrf
                                @method('PATCH')
                                <div>
                                    <label for="reset_interval_days_modal" class="mb-1 block text-sm font-medium text-gray-700">自動リセット間隔（日数）</label>
                                    <input
                                        type="number"
                                        id="reset_interval_days_modal"
                                        name="reset_interval_days"
                                        min="1"
                                        max="365"
                                        value="{{ old('reset_interval_days', $teacherDashboard['pointResetSetting']?->reset_interval_days) }}"
                                        class="w-full rounded border-gray-300"
                                        placeholder="例: 30"
                                    >
                                    <p class="mt-1 text-xs text-gray-600">空欄で自動リセットを無効化します。</p>
                                    @if ($teacherDashboard['pointResetSetting']?->last_reset_at)
                                        <p class="mt-1 text-xs text-gray-600">最終リセット: {{ $teacherDashboard['pointResetSetting']->last_reset_at->format('Y-m-d H:i') }}</p>
                                    @endif
                                </div>

                                <div class="flex justify-end gap-3">
                                    <button type="button" @click="showAutoResetModal = false" class="rounded border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">閉じる</button>
                                    <button type="submit" class="rounded bg-sky-600 px-4 py-2 text-white hover:bg-sky-700">設定を保存</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- 要件: リセット完了後のみ短時間ポップアップを表示する。 --}}
                    <div x-show="showDonePopup" class="fixed right-4 top-4 z-50 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 shadow" style="display: none;">
                        担当生徒の教科ポイントをリセットしました。
                    </div>

                <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">作成問題の正答率チェック</h3>
                                <p class="mt-1 text-sm text-gray-600">教師が作成した問題の正答率を表示します。</p>
                            </div>
                            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-center gap-4">
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="accuracy_filter" value="50" {{ $teacherDashboard['accuracyFilter'] === '50' ? 'checked' : '' }}>
                                    正答率50%以下
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="accuracy_filter" value="70" {{ $teacherDashboard['accuracyFilter'] === '70' ? 'checked' : '' }}>
                                    正答率70%以下
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="radio" name="accuracy_filter" value="all" {{ $teacherDashboard['accuracyFilter'] === 'all' ? 'checked' : '' }}>
                                    全問題
                                </label>
                                <button type="submit" class="rounded bg-gray-700 px-3 py-1.5 text-sm text-white hover:bg-gray-800">表示</button>
                            </form>
                        </div>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full border border-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="border-b px-3 py-2 text-left text-sm">問題ID</th>
                                        <th class="border-b px-3 py-2 text-left text-sm">試験</th>
                                        <th class="border-b px-3 py-2 text-left text-sm">問題文</th>
                                        <th class="border-b px-3 py-2 text-right text-sm">回答数</th>
                                        <th class="border-b px-3 py-2 text-right text-sm">正答数</th>
                                        <th class="border-b px-3 py-2 text-right text-sm">正答率</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($teacherDashboard['lowAccuracyQuestions'] as $question)
                                        <tr class="{{ $question->accuracy_rate !== null && $question->accuracy_rate <= 50 ? 'bg-red-50' : ($question->accuracy_rate !== null && $question->accuracy_rate <= 70 ? 'bg-yellow-50' : '') }}">
                                            <td class="border-b px-3 py-2 text-sm">{{ $question->id }}</td>
                                            <td class="border-b px-3 py-2 text-sm">{{ $question->exam_name ?? '未設定' }}</td>
                                            <td class="border-b px-3 py-2 text-sm">{{ \Illuminate\Support\Str::limit($question->question_text, 80) }}</td>
                                            <td class="border-b px-3 py-2 text-right text-sm">{{ $question->attempt_count }}</td>
                                            <td class="border-b px-3 py-2 text-right text-sm">{{ $question->correct_count }}</td>
                                            <td class="border-b px-3 py-2 text-right text-sm">{{ $question->accuracy_rate }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-4 text-center text-sm text-gray-500">対象の問題データがありません。</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-bold text-gray-900">教科別ポイントランキング（担当生徒）</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $teacherDashboard['teacherSubjectName'] ? $teacherDashboard['teacherSubjectName'] : '未設定教科' }} の獲得ポイント順です。進捗画面へ移動して詳細を確認できます。
                        </p>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full border border-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="border-b px-3 py-2 text-right text-sm">順位</th>
                                        <th class="border-b px-3 py-2 text-left text-sm">生徒名</th>
                                        <th class="border-b px-3 py-2 text-right text-sm">教科ポイント</th>
                                        <th class="border-b px-3 py-2 text-right text-sm">累計ポイント</th>
                                        <th class="border-b px-3 py-2 text-right text-sm">正答率</th>
                                        <th class="border-b px-3 py-2 text-left text-sm">最終FB</th>
                                        <th class="border-b px-3 py-2 text-center text-sm">進捗</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($teacherDashboard['assignedStudents'] as $index => $student)
                                        <tr class="{{ $student->feedback_overdue ? 'bg-red-50' : '' }}">
                                            <td class="border-b px-3 py-2 text-right text-sm">{{ $index + 1 }}</td>
                                            <td class="border-b px-3 py-2 text-sm">{{ $student->name }}</td>
                                            <td class="border-b px-3 py-2 text-right text-sm">{{ (int) $student->subject_points }} pt</td>
                                            <td class="border-b px-3 py-2 text-right text-sm">{{ (int) $student->total_points }} pt</td>
                                            <td class="border-b px-3 py-2 text-right text-sm">{{ $student->accuracy_rate !== null ? $student->accuracy_rate . '%' : 'データなし' }}</td>
                                            <td class="border-b px-3 py-2 text-sm {{ $student->feedback_overdue ? 'text-red-700 font-semibold' : 'text-gray-600' }}">
                                                {{ $student->last_feedback_at ? \Carbon\Carbon::parse($student->last_feedback_at)->format('Y-m-d') : '未実施' }}
                                            </td>
                                            <td class="border-b px-3 py-2 text-center text-sm">
                                                <a href="{{ route('teacher.students.progress') }}" class="rounded bg-blue-600 px-3 py-1.5 text-white hover:bg-blue-700">進捗画面へ</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="px-3 py-4 text-center text-sm text-gray-500">担当生徒がまだ設定されていません。</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                </div>
            @elseif (Auth::user()->isStudent() && $studentDashboard)
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">担任フィードバックコメント</h3>
                                    <p class="mt-1 text-sm text-gray-600">最新のコメントを表示しています。</p>
                                </div>
                                <a href="{{ route('dashboard.feedback-history') }}" class="rounded bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700">
                                    FB履歴
                                </a>
                            </div>

                            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                                @if ($studentDashboard['latestFeedback'])
                                    <div class="text-xs text-gray-500">
                                        {{ $studentDashboard['latestFeedback']->created_at->format('Y-m-d H:i') }} / {{ $studentDashboard['latestFeedback']->teacher?->name ?? '担任' }}
                                    </div>
                                    <p class="mt-2 text-sm text-gray-800 whitespace-pre-wrap">{{ $studentDashboard['latestFeedback']->comment }}</p>
                                @else
                                    <p class="text-sm text-gray-500">フィードバックコメントはまだありません。</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-bold text-gray-900">自分の所持ポイント</h3>
                            <p class="mt-1 text-sm text-gray-600">ポイント合計と順位情報です。</p>

                            <div class="mt-4 flex items-end justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-4">
                                <div>
                                    <div class="text-xs font-semibold text-emerald-800">所持ポイント</div>
                                    <div class="mt-1 text-3xl font-extrabold text-emerald-900">{{ (int) $studentDashboard['totalPoints'] }} <span class="text-base font-semibold">pt</span></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs font-semibold text-gray-600">現在順位（全生徒）</div>
                                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ $studentDashboard['pointRank'] !== null ? $studentDashboard['pointRank'] : '-' }} 位</div>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-600">担任ごとの教科ポイント順位は下部の教科別ポイントランキングで確認できます。</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-bold text-gray-900">ポイント獲得履歴（最新10件）</h3>
                        <p class="mt-1 text-sm text-gray-600">どの問題でポイントを獲得したか、またはリセットされたかを確認できます。</p>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full border border-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="border-b px-3 py-2 text-left text-sm">日時</th>
                                        <th class="border-b px-3 py-2 text-left text-sm">種別</th>
                                        <th class="border-b px-3 py-2 text-left text-sm">教科/担任</th>
                                        <th class="border-b px-3 py-2 text-left text-sm">問題</th>
                                        <th class="border-b px-3 py-2 text-right text-sm">増減</th>
                                        <th class="border-b px-3 py-2 text-right text-sm">反映後残高</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($studentDashboard['recentPointHistories'] as $pointHistory)
                                        <tr>
                                            <td class="border-b px-3 py-2 text-sm">{{ $pointHistory->created_at->format('Y-m-d H:i') }}</td>
                                            <td class="border-b px-3 py-2 text-sm">{{ $pointHistory->event_type }}</td>
                                            <td class="border-b px-3 py-2 text-sm">{{ $pointHistory->teacher?->subject_name ?? '-' }} / {{ $pointHistory->teacher?->name ?? '-' }}</td>
                                            <td class="border-b px-3 py-2 text-sm">{{ $pointHistory->question?->id ? 'ID:' . $pointHistory->question->id : '-' }}</td>
                                            <td class="border-b px-3 py-2 text-right text-sm {{ $pointHistory->points_delta >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $pointHistory->points_delta >= 0 ? '+' : '' }}{{ (int) $pointHistory->points_delta }}</td>
                                            <td class="border-b px-3 py-2 text-right text-sm">{{ (int) $pointHistory->balance_after }} pt</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-4 text-center text-sm text-gray-500">履歴データがありません。</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-bold text-gray-900">正答率が低い問題（ランダム3問）</h3>
                        <p class="mt-1 text-sm text-gray-600">あなたの解答履歴から正答率が低い問題をランダム表示しています。</p>

                        <div class="mt-4 grid gap-4 md:grid-cols-3">
                            @forelse ($studentDashboard['weakQuestions'] as $question)
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                    <div class="text-xs font-semibold text-amber-800">問題ID: {{ $question->id }} / {{ $question->exam_name ?? '試験未設定' }}</div>
                                    <p class="mt-2 text-sm text-gray-800">{{ \Illuminate\Support\Str::limit($question->question_text, 90) }}</p>
                                    <div class="mt-3 flex items-center justify-between text-xs text-gray-600">
                                        <span>回答 {{ $question->attempt_count }} 回</span>
                                        <span class="font-semibold text-amber-900">正答率 {{ $question->accuracy_rate }}%</span>
                                    </div>
                                </div>
                            @empty
                                <div class="md:col-span-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                                    解答履歴がまだないため、表示できる問題がありません。
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-bold text-gray-900">全生徒ポイントランキング</h3>
                        <p class="mt-1 text-sm text-gray-600">上位3位とあなたの順位を表示しています。</p>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full border border-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="border-b px-3 py-2 text-right text-sm">順位</th>
                                        <th class="border-b px-3 py-2 text-left text-sm">生徒名</th>
                                        <th class="border-b px-3 py-2 text-right text-sm">ポイント</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($studentDashboard['globalRankingDisplayStudents'] as $rankedStudent)
                                        @php
                                            $rankRowClass = match ($rankedStudent->point_rank) {
                                                1 => 'bg-yellow-100',
                                                2 => 'bg-slate-100',
                                                3 => 'bg-orange-100',
                                                default => ($rankedStudent->id === Auth::id() ? 'bg-rose-50' : ''),
                                            };
                                        @endphp
                                        <tr class="{{ $rankRowClass }}">
                                            <td class="border-b px-3 py-2 text-right text-sm">
                                                @if ($rankedStudent->point_rank === 1)
                                                    <span class="mr-1" aria-hidden="true">👑</span>
                                                @endif
                                                {{ $rankedStudent->point_rank !== null ? $rankedStudent->point_rank : '-' }} 位
                                            </td>
                                            <td class="border-b px-3 py-2 text-sm {{ $rankedStudent->id === Auth::id() ? 'font-semibold text-rose-700' : '' }}">{{ $rankedStudent->name }}</td>
                                            <td class="border-b px-3 py-2 text-right text-sm">{{ (int) $rankedStudent->total_points }} pt</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-3 py-4 text-center text-sm text-gray-500">表示できる生徒データがありません。</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-bold text-gray-900">教科別ポイントランキング</h3>
                        <p class="mt-1 text-sm text-gray-600">ランキングは担任ごとの受け持ち生徒単位で集計し、上位3位とあなたの順位を表示しています。</p>

                        <div class="mt-4 space-y-6">
                            @forelse ($studentDashboard['teacherRankings'] as $teacherRanking)
                                <div>
                                    <h4 class="text-base font-semibold text-gray-800">
                                        {{ $teacherRanking['subjectName'] ?? '未設定教科' }} / {{ $teacherRanking['teacher']->name }} 先生
                                    </h4>
                                    <p class="mt-1 text-xs text-gray-600">
                                        {{ $teacherRanking['studentCount'] }}名中 {{ $teacherRanking['currentStudentRank'] ?? '-' }}位
                                    </p>

                                    <div class="mt-2 overflow-x-auto">
                                        <table class="min-w-full border border-gray-200">
                                            <thead>
                                                <tr class="bg-gray-50">
                                                    <th class="border-b px-3 py-2 text-right text-sm">順位</th>
                                                    <th class="border-b px-3 py-2 text-left text-sm">生徒名</th>
                                                    <th class="border-b px-3 py-2 text-right text-sm">ポイント</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($teacherRanking['displayStudents'] as $rankedStudent)
                                                    @php
                                                        $rankRowClass = match ($rankedStudent->point_rank) {
                                                            1 => 'bg-yellow-100',
                                                            2 => 'bg-slate-100',
                                                            3 => 'bg-orange-100',
                                                            default => ($rankedStudent->id === Auth::id() ? 'bg-rose-50' : ''),
                                                        };
                                                    @endphp
                                                    <tr class="{{ $rankRowClass }}">
                                                        <td class="border-b px-3 py-2 text-right text-sm">
                                                            @if ($rankedStudent->point_rank === 1)
                                                                <span class="mr-1" aria-hidden="true">👑</span>
                                                            @endif
                                                            {{ $rankedStudent->point_rank !== null ? $rankedStudent->point_rank : '-' }} 位
                                                        </td>
                                                        <td class="border-b px-3 py-2 text-sm {{ $rankedStudent->id === Auth::id() ? 'font-semibold text-rose-700' : '' }}">{{ $rankedStudent->name }}</td>
                                                        <td class="border-b px-3 py-2 text-right text-sm">{{ (int) $rankedStudent->total_points }} pt</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="px-3 py-4 text-center text-sm text-gray-500">受け持ち生徒がいません。</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                                    担任が未設定のため、表示できるランキングがありません。
                                </div>
                            @endforelse
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
