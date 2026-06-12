<x-app-layout>
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold mb-6 text-center text-blue-700">{{ $exam->name }}</h1> {{-- 試験名を表示 --}}
        <h2 class="text-2xl font-semibold mb-2 text-center text-gray-700">問題</h2>

        @php
            $modeLabel = match ($quiz_mode ?? 'normal') {
                'flagged' => 'フラグ付きモード',
                'random_count' => '指定数ランダム出題モード',
                default => '通常モード',
            };
        @endphp

        <p class="text-center text-sm text-gray-600 mb-1">{{ $modeLabel }}</p>
        <p class="text-center text-sm text-gray-600 mb-6">正解済み {{ $progress_correct }} / {{ $progress_total }} ・ 残り {{ $remaining_count }}</p>

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if ($question)
            <div class="mb-8">
                <p class="text-lg font-semibold mb-4">問題 {{ $question->id }}:</p>
                <p class="text-xl leading-relaxed bg-blue-50 p-4 rounded-md border border-blue-200">{!! nl2br(e($question->question_text)) !!}</p>
                @if ($question->question_image_path)
                    <div class="mt-4">
                        <img src="{{ asset('storage/' . $question->question_image_path) }}" alt="問題画像" class="max-h-80 rounded-md border border-blue-200">
                    </div>
                @endif
            </div>

            <form action="{{ route('quiz.answer', $exam) }}" method="POST">
                @csrf
                <input type="hidden" name="question_id" value="{{ $question->id }}">

                <div class="space-y-4 mb-8">
                    @foreach ($shuffled_options as $option)
                        <label class="block cursor-pointer bg-gray-50 hover:bg-gray-100 p-4 rounded-lg border border-gray-200 shadow-sm">
                            <input type="checkbox" name="selected_options[]" value="{{ $option->id }}" class="mr-3 form-checkbox text-blue-600 focus:ring-blue-500">
                            <span class="text-lg text-gray-800">{!! nl2br(e($option->option_text)) !!}</span>
                            @if ($option->option_image_path)
                                <div class="mt-3">
                                    <img src="{{ asset('storage/' . $option->option_image_path) }}" alt="選択肢画像" class="max-h-48 rounded-md border border-gray-200">
                                </div>
                            @endif
                        </label>
                    @endforeach
                </div>

                <div class="text-center">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-full text-xl shadow-lg transition duration-300 ease-in-out transform hover:scale-105">
                        解答する
                    </button>
                </div>
            </form>

            @if (auth()->user()?->isStudent())
                <div id="quiz-reset-warning-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4" aria-hidden="true">
                    <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                        <h3 class="text-lg font-bold text-gray-900">出題状態の警告</h3>
                        <p class="mt-2 text-sm text-gray-700">この画面から移動すると、現在の出題状態が解除される可能性があります。移動しますか？</p>
                        <div class="mt-5 flex justify-end gap-3">
                            <button type="button" id="quiz-reset-warning-cancel" class="rounded border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">キャンセル</button>
                            <button type="button" id="quiz-reset-warning-continue" class="rounded bg-red-600 px-4 py-2 text-white hover:bg-red-700">移動する</button>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const modal = document.getElementById('quiz-reset-warning-modal');
                        const cancelButton = document.getElementById('quiz-reset-warning-cancel');
                        const continueButton = document.getElementById('quiz-reset-warning-continue');
                        let onContinue = null;

                        if (!modal || !cancelButton || !continueButton) {
                            return;
                        }

                        const openModal = (callback) => {
                            onContinue = callback;
                            modal.classList.remove('hidden');
                            modal.classList.add('flex');
                            modal.setAttribute('aria-hidden', 'false');
                        };

                        const closeModal = () => {
                            onContinue = null;
                            modal.classList.remove('flex');
                            modal.classList.add('hidden');
                            modal.setAttribute('aria-hidden', 'true');
                        };

                        cancelButton.addEventListener('click', closeModal);
                        modal.addEventListener('click', (event) => {
                            if (event.target === modal) {
                                closeModal();
                            }
                        });
                        continueButton.addEventListener('click', () => {
                            const callback = onContinue;
                            closeModal();
                            if (typeof callback === 'function') {
                                callback();
                            }
                        });

                        document.querySelectorAll('a[href]').forEach((link) => {
                            link.addEventListener('click', (event) => {
                                const href = link.getAttribute('href');
                                if (!href || href.startsWith('#')) {
                                    return;
                                }

                                event.preventDefault();
                                openModal(() => {
                                    window.location.href = href;
                                });
                            });
                        });
                    });
                </script>
            @endif
        @else
            <p class="text-center text-gray-600">この試験には現在、出題できる問題がありません。</p>
            <div class="mt-8 text-center">
                <a href="{{ route('questions.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    新しい問題を作成する
                </a>
            </div>
        @endif

        @if (!auth()->user()?->isStudent())
            <div class="mt-10 text-center">
                <a href="{{ route('quiz.select_exam') }}" class="text-blue-500 hover:text-blue-800 text-sm">
                    他の試験を選択する
                </a>

                @if (($quiz_mode ?? 'normal') !== 'random_count')
                    <span class="text-gray-400 mx-2">|</span>
                    <a href="{{ route('questions.index') }}" class="text-blue-500 hover:text-blue-800 text-sm">
                        問題管理画面へ戻る
                    </a>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>