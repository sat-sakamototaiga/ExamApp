<x-app-layout>
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold mb-6 text-center">問題一覧</h1>

        {{-- 既存のボタンエリア --}}
        <div class="mb-6 text-right">
            <a href="{{ route('questions.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                新しい問題を作成
            </a>
            <a href="{{ route('questions.import.form') }}" class="ml-4 bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                問題をインポート
            </a>
            <a href="{{ route('exams.index') }}" class="ml-4 bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                試験を管理
            </a>
        </div>

        {{-- 絞り込みフォーム --}}
        <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
            <form method="GET" action="{{ route('questions.index') }}">
                <div class="flex items-end space-x-4">
                    {{-- 試験選択ドロップダウン --}}
                    <div class="flex-grow">
                        <label for="exam_id" class="block font-medium text-sm text-gray-700">試験を選択して表示</label>
                        <select name="exam_id" id="exam_id" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">-- 試験を選択 --</option>
                            @foreach ($exams as $exam)
                                <option value="{{ $exam->id }}" {{ $selectedExamId == $exam->id ? 'selected' : '' }}>
                                    {{ $exam->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- フラグフィルター --}}
                    <div>
                        <label class="block font-medium text-sm text-gray-700">フラグ</label>
                        <div class="mt-2 space-x-4">
                            <label><input type="radio" name="filter" value="all" class="mr-1" {{ $filter !== 'flagged' ? 'checked' : '' }}>すべて</label>
                            <label><input type="radio" name="filter" value="flagged" class="mr-1" {{ $filter === 'flagged' ? 'checked' : '' }}>フラグ付きのみ</label>
                        </div>
                    </div>

                    {{-- 送信ボタン --}}
                    <div>
                        <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            表示
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- 試験が選択されている場合のみテーブルを表示 --}}
        @if ($selectedExamId)
            @if ($questions->isEmpty())
                <p class="text-center text-gray-600">この条件に一致する問題はありません。</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b text-left w-24">問題番号</th>
                                <th class="py-2 px-4 border-b text-left">試験名</th>
                                <th class="py-2 px-4 border-b text-left">問題文</th>
                                <th class="py-2 px-4 border-b text-center" title="フラグ">⭐</th>
                                <th class="py-2 px-4 border-b text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($questions as $question)
                                <tr>
                                    <td class="py-2 px-4 border-b">{{ ($questions->currentPage() - 1) * $questions->perPage() + $loop->iteration }}</td>
                                    <td class="py-2 px-4 border-b">{{ $question->exam->name ?? 'N/A' }}</td>
                                    <td class="py-2 px-4 border-b max-w-xs overflow-hidden text-ellipsis whitespace-nowrap">{!! nl2br(e(Str::limit($question->question_text, 50))) !!}</td>
                                    
                                    {{-- ★★★ ここから変更 ★★★ --}}
                                    <td class="py-2 px-4 border-b text-center">
                                        @if ($flaggedQuestionIds->contains($question->id))
                                            <span class="text-yellow-500">✔️</span>
                                        @endif
                                    </td>
                                    {{-- ★★★ ここまで変更 ★★★ --}}
                                    
                                    <td class="py-2 px-4 border-b text-center whitespace-nowrap">
                                        <a href="{{ route('questions.edit', $question) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-3 rounded text-sm mr-2">編集</a>
                                        <form action="{{ route('questions.destroy', $question) }}" method="POST" class="inline-block" onsubmit="return confirm('本当にこの問題を削除しますか？');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm">削除</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $questions->links() }}
                </div>
            @endif
        @else
            <p class="text-center text-gray-600">上のフォームで試験を選択して「表示」ボタンを押してください。</p>
        @endif

    </div>
</x-app-layout>