<x-app-layout>
    <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-4">全ユーザー正答率（管理者）</h1>

        <div class="mb-4 flex gap-3">
            <a href="{{ route('admin.users.index') }}" class="rounded bg-slate-600 px-4 py-2 text-white hover:bg-slate-700">ユーザー一覧へ</a>
            <a href="{{ route('admin.teacher-students.index') }}" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">教師-生徒の管理</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="border-b px-3 py-2 text-left">ID</th>
                        <th class="border-b px-3 py-2 text-left">名前</th>
                        <th class="border-b px-3 py-2 text-left">ロール</th>
                        <th class="border-b px-3 py-2 text-right">総正解数</th>
                        <th class="border-b px-3 py-2 text-right">総問題数</th>
                        <th class="border-b px-3 py-2 text-right">正答率</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="border-b px-3 py-2">{{ $row->id }}</td>
                            <td class="border-b px-3 py-2">{{ $row->name }}</td>
                            <td class="border-b px-3 py-2">{{ $row->role }}</td>
                            <td class="border-b px-3 py-2 text-right">{{ $row->total_score }}</td>
                            <td class="border-b px-3 py-2 text-right">{{ $row->total_questions }}</td>
                            <td class="border-b px-3 py-2 text-right">{{ $row->accuracy_rate !== null ? $row->accuracy_rate . '%' : 'データなし' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-center text-gray-500">成績データがありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $rows->links() }}
        </div>
    </div>
</x-app-layout>
