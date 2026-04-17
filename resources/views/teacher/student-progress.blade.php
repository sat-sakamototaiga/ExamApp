<x-app-layout>
    <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-4">担当生徒の正答率とFB（教師）</h1>

        @if (session('success'))
            <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-2 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-2 text-red-700">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-6 overflow-x-auto">
            <table class="min-w-full border border-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="border-b px-3 py-2 text-left">生徒名</th>
                        <th class="border-b px-3 py-2 text-left">メール</th>
                        <th class="border-b px-3 py-2 text-right">総正解数</th>
                        <th class="border-b px-3 py-2 text-right">総問題数</th>
                        <th class="border-b px-3 py-2 text-right">正答率</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td class="border-b px-3 py-2">{{ $student->name }}</td>
                            <td class="border-b px-3 py-2">{{ $student->email }}</td>
                            <td class="border-b px-3 py-2 text-right">{{ $student->total_score }}</td>
                            <td class="border-b px-3 py-2 text-right">{{ $student->total_questions }}</td>
                            <td class="border-b px-3 py-2 text-right">{{ $student->accuracy_rate !== null ? $student->accuracy_rate . '%' : 'データなし' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-gray-500">担当生徒がまだ設定されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mb-8 rounded border border-gray-200 p-4">
            <h2 class="mb-3 text-lg font-semibold">担当生徒へのフィードバックコメント</h2>
            <form action="{{ route('teacher.students.feedback.store') }}" method="POST" class="grid gap-3">
                @csrf
                <div>
                    <label for="student_id" class="mb-1 block text-sm font-medium text-gray-700">生徒</label>
                    <select id="student_id" name="student_id" class="w-full rounded border-gray-300">
                        <option value="">選択してください</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="comment" class="mb-1 block text-sm font-medium text-gray-700">コメント</label>
                    <textarea id="comment" name="comment" rows="3" class="w-full rounded border-gray-300" placeholder="例: 次回は設問3の解説を重点的に復習しましょう。"></textarea>
                </div>
                <div>
                    <button type="submit" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">保存</button>
                </div>
            </form>
        </div>

        <div>
            <h2 class="mb-3 text-lg font-semibold">直近のコメント履歴</h2>
            <div class="space-y-2">
                @forelse ($feedbackByStudent as $feedback)
                    <div class="rounded border border-gray-200 px-3 py-2">
                        <div class="text-sm text-gray-500">{{ $feedback->created_at->format('Y-m-d H:i') }} / {{ $feedback->student->name }}</div>
                        <div class="mt-1 text-gray-800">{{ $feedback->comment }}</div>
                    </div>
                @empty
                    <p class="text-gray-500">コメント履歴はまだありません。</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
