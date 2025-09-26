<x-app-layout>
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold mb-6 text-center">試験一覧</h1>

        <div class="mb-6 text-right">
            <a href="{{ route('exams.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                新しい試験を作成
            </a>
        </div>

        @if ($exams->isEmpty())
            <p class="text-center text-gray-600">まだ試験が登録されていません。</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b text-left">ID</th>
                            <th class="py-2 px-4 border-b text-left">試験名</th>
                            <th class="py-2 px-4 border-b text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($exams as $exam)
                            <tr>
                                <td class="py-2 px-4 border-b">{{ $exam->id }}</td>
                                <td class="py-2 px-4 border-b">{{ $exam->name }}</td>
                                <td class="py-2 px-4 border-b text-center whitespace-nowrap">
                                    <a href="{{ route('exams.edit', $exam) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-3 rounded text-sm mr-2">編集</a>
                                    <form action="{{ route('exams.destroy', $exam) }}" method="POST" class="inline-block" onsubmit="return confirm('本当にこの試験を削除しますか？\nこの試験に紐づく全ての問題も削除されます。');">
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
                {{ $exams->links() }}
            </div>
        @endif
    </div>
</x-app-layout>