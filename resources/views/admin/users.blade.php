<x-app-layout>
    <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-4">ユーザー一覧（管理者）</h1>

        @if (session('success'))
            <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-2 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-red-800">
                <p class="font-bold">入力内容を確認してください。</p>
                <ul class="mt-2 list-disc ps-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-4 flex gap-3">
            <a href="{{ route('admin.users.accuracy') }}" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">全ユーザー正答率</a>
            <a href="{{ route('admin.teacher-students.index') }}" class="rounded bg-slate-600 px-4 py-2 text-white hover:bg-slate-700">教師-生徒の管理</a>
        </div>

        @php
            $openIndividualForm = old('form_type') === 'individual';
            $openCsvForm = old('form_type') === 'csv';
            $teacherPositions = \App\Models\User::TEACHER_POSITIONS;
            $tableColspan = $selectedRole === 'teacher' ? 6 : (in_array($selectedRole, ['student'], true) ? 4 : 3);
        @endphp

        @if ($openIndividualForm)
            <div x-data x-init="$nextTick(() => $dispatch('open-modal', 'register-user-modal'))"></div>
        @endif
        @if ($openCsvForm)
            <div x-data x-init="$nextTick(() => $dispatch('open-modal', 'import-user-csv-modal'))"></div>
        @endif

        <div class="mb-4 flex flex-wrap gap-2">
            <a href="{{ route('admin.users.index', ['role' => 'admin']) }}"
               class="rounded px-4 py-2 text-sm font-semibold {{ $selectedRole === 'admin' ? 'bg-slate-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                管理者
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'teacher']) }}"
               class="rounded px-4 py-2 text-sm font-semibold {{ $selectedRole === 'teacher' ? 'bg-slate-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                教師
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'student']) }}"
               class="rounded px-4 py-2 text-sm font-semibold {{ $selectedRole === 'student' ? 'bg-slate-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                生徒
            </a>
        </div>

        <div class="mb-6 flex flex-wrap gap-3">
            <button type="button"
                    x-data
                    x-on:click.prevent="$dispatch('open-modal', 'register-user-modal')"
                    class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">
                教師・生徒を個別登録
            </button>
            <button type="button"
                    x-data
                    x-on:click.prevent="$dispatch('open-modal', 'import-user-csv-modal')"
                    class="rounded bg-slate-700 px-4 py-2 text-white hover:bg-slate-800">
                CSVで一括登録
            </button>
        </div>

        <x-modal name="register-user-modal" maxWidth="lg" focusable>
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900">教師・生徒を個別登録</h2>
                <p class="mt-1 text-sm text-gray-600">管理者が教師または生徒アカウントを直接追加できます。</p>

                <form action="{{ route('admin.users.store') }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="form_type" value="individual">

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">名前</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">メールアドレス</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">ロール</label>
                        <select id="role" name="role" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">選択してください</option>
                            <option value="teacher" {{ old('role') === 'teacher' ? 'selected' : '' }}>教師</option>
                            <option value="student" {{ old('role') === 'student' ? 'selected' : '' }}>生徒</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">教師を選択した場合は教科名が必須です。</p>
                    </div>

                    <div>
                        <label for="subject_name" class="block text-sm font-medium text-gray-700">教科名（教師は必須）</label>
                        <input id="subject_name" name="subject_name" type="text" value="{{ old('subject_name') }}" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="例: 数学">
                    </div>

                    <div>
                        <label for="position" class="block text-sm font-medium text-gray-700">役職（教師は必須）</label>
                        <select id="position" name="position" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">選択してください</option>
                            @foreach ($teacherPositions as $position)
                                <option value="{{ $position }}" {{ old('position') === $position ? 'selected' : '' }}>{{ $position }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">初期パスワード</label>
                        <input id="password" name="password" type="password" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">初期パスワード（確認）</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button"
                                x-data
                                x-on:click="$dispatch('close-modal', 'register-user-modal')"
                                class="rounded border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-100">
                            キャンセル
                        </button>
                        <button type="submit" class="rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">ユーザーを登録</button>
                    </div>
                </form>
            </div>
        </x-modal>

        <x-modal name="import-user-csv-modal" maxWidth="lg" focusable>
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900">CSVで一括登録</h2>
                <p class="mt-1 text-sm text-gray-600">教師・生徒アカウントを CSV からまとめて追加できます。</p>

                <div class="mt-4 rounded bg-gray-50 p-4 text-sm text-gray-700">
                    <p>CSV ヘッダー形式:</p>
                    <p class="mt-1 font-mono text-xs break-all">名前,メールアドレス,ロール,教科名,役職,パスワード</p>
                    <p class="mt-2">ロールは teacher または student を指定してください。teacher の場合は教科名と役職を必ず入力し、パスワードは 8 文字以上です。</p>
                    <a href="{{ route('admin.users.import.template') }}" class="mt-3 inline-flex rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">CSVテンプレートをダウンロード</a>
                </div>

                <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="form_type" value="csv">

                    <div>
                        <label for="csv_file" class="block text-sm font-medium text-gray-700">CSVファイル</label>
                        <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt" class="mt-1 block w-full text-sm text-gray-500 file:me-4 file:rounded-full file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700 hover:file:bg-blue-100" required>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button"
                                x-data
                                x-on:click="$dispatch('close-modal', 'import-user-csv-modal')"
                                class="rounded border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-100">
                            キャンセル
                        </button>
                        <button type="submit" class="rounded bg-slate-700 px-4 py-2 text-white hover:bg-slate-800">CSVをインポート</button>
                    </div>
                </form>
            </div>
        </x-modal>

        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="border-b px-3 py-2 text-left">名前</th>
                        <th class="border-b px-3 py-2 text-left">メール</th>
                        <th class="border-b px-3 py-2 text-left">ロール</th>
                        @if ($selectedRole === 'teacher')
                            <th class="border-b px-3 py-2 text-left">教科名</th>
                            <th class="border-b px-3 py-2 text-left">役職</th>
                            <th class="border-b px-3 py-2 text-right">担当生徒数</th>
                        @elseif ($selectedRole === 'student')
                            <th class="border-b px-3 py-2 text-right">担当教師数</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="border-b px-3 py-2">{{ $user->name }}</td>
                            <td class="border-b px-3 py-2">{{ $user->email }}</td>
                            <td class="border-b px-3 py-2">{{ $user->role }}</td>
                            @if ($selectedRole === 'teacher')
                                <td class="border-b px-3 py-2">{{ $user->subject_name }}</td>
                                <td class="border-b px-3 py-2">{{ $user->position }}</td>
                                <td class="border-b px-3 py-2 text-right">{{ $user->students_count }}</td>
                            @elseif ($selectedRole === 'student')
                                <td class="border-b px-3 py-2 text-right">{{ $user->teachers_count }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tableColspan }}" class="px-3 py-4 text-center text-gray-500">該当するユーザーが存在しません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
