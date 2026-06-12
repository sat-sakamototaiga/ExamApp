<x-app-layout>
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold mb-8 text-center text-blue-700">受験する試験を選択してください</h1>

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if ($exams->isEmpty())
            @if (auth()->user()?->isStudent())
                <p class="text-center text-gray-600 mb-6">現在受験可能な試験はありません。管理者または教師にお問い合わせください。</p>
            @else
                <p class="text-center text-gray-600 mb-6">まだ試験が登録されていません。まずは問題管理画面で試験と問題を作成してください。</p>
                <div class="text-center">
                    <a href="{{ route('exams.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        新しい試験を作成
                    </a>
                </div>
            @endif
        @else
            <form method="GET" class="space-y-6" action="{{ route('quiz.index', ['exam' => 0]) }}" id="quiz-start-form">
                <div>
                    <label for="exam_id" class="block text-sm font-medium text-gray-700 mb-2">試験</label>
                    <select id="exam_id" name="exam_id" class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500" required>
                        <option value="">選択してください</option>
                        @foreach ($exams as $exam)
                            <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <p class="block text-sm font-medium text-gray-700 mb-2">出題方式</p>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="mode" value="normal" class="text-blue-600 focus:ring-blue-500" checked>
                            <span>通常モード（試験内の全問題から出題）</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="mode" value="flagged" class="text-blue-600 focus:ring-blue-500">
                            <span>フラグ付きモード（自分がフラグを付けた問題のみ出題）</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="mode" value="random_count" class="text-blue-600 focus:ring-blue-500">
                            <span>指定数ランダム出題モード</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="count" class="block text-sm font-medium text-gray-700 mb-2">指定問題数（ランダム出題モードのみ）</label>
                    <input id="count" name="count" type="number" min="1" max="200" value="10" class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500" disabled>
                    <p class="text-xs text-gray-500 mt-2">指定数ランダム出題モードでは、リロード以外の画面遷移は制限されます。</p>
                </div>

                <div class="text-center">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-full text-lg shadow-md">
                        この条件で開始
                    </button>
                </div>
            </form>

            <script>
                (() => {
                    const form = document.getElementById('quiz-start-form');
                    if (!form) {
                        return;
                    }

                    const examSelect = document.getElementById('exam_id');
                    const countInput = document.getElementById('count');
                    const modeInputs = form.querySelectorAll('input[name="mode"]');

                    const updateCountState = () => {
                        const selectedMode = form.querySelector('input[name="mode"]:checked')?.value;
                        const isRandomCount = selectedMode === 'random_count';
                        countInput.disabled = !isRandomCount;
                        countInput.required = isRandomCount;
                    };

                    modeInputs.forEach((input) => {
                        input.addEventListener('change', updateCountState);
                    });

                    form.addEventListener('submit', (event) => {
                        const examId = examSelect.value;
                        if (!examId) {
                            return;
                        }

                        event.preventDefault();
                        const actionTemplate = "{{ route('quiz.index', ['exam' => '__EXAM__']) }}";
                        form.action = actionTemplate.replace('__EXAM__', examId);
                        form.submit();
                    });

                    updateCountState();
                })();
            </script>
        @endif

        @if (!auth()->user()?->isStudent())
            <div class="mt-8 text-center">
                <a href="{{ route('exams.index') }}" class="text-purple-500 hover:text-purple-800 text-sm">
                    試験管理画面へ
                </a>
                <span class="text-gray-400 mx-2">|</span>
                <a href="{{ route('questions.index') }}" class="text-blue-500 hover:text-blue-800 text-sm">
                    問題管理画面へ
                </a>
            </div>
        @endif
    </div>
</x-app-layout>