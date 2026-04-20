<x-app-layout>
    <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-4">教師ユーザーの生徒管理（管理者）</h1>

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

        <div class="mb-6 flex gap-3">
            <a href="{{ route('admin.users.index') }}" class="rounded bg-slate-600 px-4 py-2 text-white hover:bg-slate-700">ユーザー一覧へ</a>
            <a href="{{ route('admin.users.accuracy') }}" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">全ユーザー正答率</a>
        </div>

        <form action="{{ route('admin.teacher-students.store') }}" method="POST" class="mb-8 grid gap-4 md:grid-cols-3">
            @csrf
            <div>
                <label for="teacher_id" class="mb-1 block text-sm font-medium text-gray-700">教師</label>
                <select id="teacher_id" name="teacher_id" class="w-full rounded border-gray-300">
                    <option value="">選択してください</option>
                    @foreach ($teachers as $teacher)
                        <option value="{{ $teacher->id }}" {{ (string) old('teacher_id') === (string) $teacher->id ? 'selected' : '' }}>{{ $teacher->name }} ({{ $teacher->email }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="student_ids" class="mb-1 block text-sm font-medium text-gray-700">生徒</label>
                <select id="student_ids" name="student_ids[]" multiple size="8" class="w-full rounded border-gray-300">
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(in_array((string) $student->id, array_map('strval', old('student_ids', [])), true))>{{ $student->name }} ({{ $student->email }})</option>
                    @endforeach
                </select>
                <p class="mt-1 text-sm text-gray-500">Ctrl または Shift を使って複数選択できます。</p>
            </div>
            <div class="flex items-end">
                <button type="submit" class="rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700">紐付けを追加</button>
            </div>
        </form>

        <div class="space-y-4">
            @forelse ($teachers as $teacher)
                <div class="rounded border border-gray-200 p-4">
                    <details {{ (string) old('teacher_id') === (string) $teacher->id ? 'open' : '' }}>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded px-2 py-1 hover:bg-gray-50">
                            <div>
                                <h2 class="text-lg font-semibold">{{ $teacher->name }} ({{ $teacher->email }})</h2>
                                <p class="text-sm text-gray-500">担当生徒数: {{ $teacher->students->count() }}人</p>
                            </div>
                            <span class="text-sm font-medium text-gray-500">開閉</span>
                        </summary>

                        <div class="mt-3 border-t border-gray-100 pt-3">
                            @if ($teacher->students->isEmpty())
                                <p class="text-sm text-gray-500">担当生徒は未設定です。</p>
                            @else
                                <ul class="space-y-2">
                                    @foreach ($teacher->students as $student)
                                        <li class="flex items-center justify-between rounded bg-gray-50 px-3 py-2">
                                            <span>{{ $student->name }} ({{ $student->email }})</span>
                                            <form action="{{ route('admin.teacher-students.destroy', ['teacher' => $teacher->id, 'student' => $student->id]) }}" method="POST" onsubmit="return confirm('この紐付けを解除しますか？');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded bg-red-600 px-3 py-1 text-sm text-white hover:bg-red-700">解除</button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </details>
                </div>
            @empty
                <p class="text-gray-500">教師ユーザーが存在しません。</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
