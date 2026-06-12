<x-app-layout>
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-md">
        @php
            $modeLabel = match ($quiz_mode ?? 'normal') {
                'flagged' => 'フラグ付きモード',
                'random_count' => '指定数ランダム出題モード',
                default => '通常モード',
            };
        @endphp

        <h1 class="text-3xl font-bold mb-2 text-center @if($is_correct) text-green-700 @else text-red-700 @endif">
            @if($is_correct)
                正解！
            @else
                不正解...
            @endif
        </h1>
        <p class="text-center text-sm text-gray-600 mb-1">{{ $modeLabel }}</p>
        <p class="text-center text-sm text-gray-600 mb-6">正解済み {{ $progress_correct }} / {{ $progress_total }} ・ 残り {{ $remaining_count }}</p>

        @if ($is_correct)
            <div class="mb-6 rounded border border-blue-300 bg-blue-50 px-4 py-3 text-blue-800">
                今回の正解で <strong>{{ $awarded_question_points ?? 0 }}pt</strong> を獲得しました。
                @if (($awarded_bonus_points ?? 0) > 0)
                    全問正解ボーナスとして <strong>{{ $awarded_bonus_points }}pt</strong> を追加しました。
                @endif
            </div>
        @endif

        @if ($is_finished)
            <div class="mb-6 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">
                全ての問題に正解したため、出題を終了しました。
            </div>
        @endif

        <div class="mb-6">
            <div class="mb-4 rounded border border-gray-200 bg-white p-4">
                <p class="text-base font-semibold text-gray-800">問題:</p>
                <p class="mt-2 text-gray-800 leading-relaxed">{!! nl2br(e($question->question_text)) !!}</p>
                @if ($question->question_image_path)
                    <div class="mt-3">
                        <img src="{{ asset('storage/' . $question->question_image_path) }}" alt="問題画像" class="max-h-80 rounded-md border border-gray-200">
                    </div>
                @endif
            </div>

            <p class="text-lg font-semibold mb-4">選択肢の詳細と解説:</p>
            <div class="space-y-4">
                @foreach ($question_options as $option)
                    @php
                        $is_selected = in_array($option->id, $selected_option_ids);
                        $is_actual_correct = $option->is_correct;
                        $bg_class = '';
                        if ($is_selected && $is_actual_correct) {
                            $bg_class = 'bg-green-100 border-green-400';
                        } elseif ($is_selected && !$is_actual_correct) {
                            $bg_class = 'bg-red-100 border-red-400';
                        } elseif (!$is_selected && $is_actual_correct) {
                            $bg_class = 'bg-yellow-100 border-yellow-400';
                        } else {
                            $bg_class = 'bg-gray-50 border-gray-200';
                        }
                    @endphp
                    <div class="p-4 rounded-lg border {{ $bg_class }}">
                        <p class="text-lg">
                            <span class="font-semibold">{{ $option->option_text }}</span>
                            @if ($is_actual_correct)
                                <span class="ml-2 px-2 py-1 bg-green-500 text-white text-xs rounded-full">正解</span>
                            @endif
                            @if ($is_selected)
                                <span class="ml-2 px-2 py-1 bg-blue-500 text-white text-xs rounded-full">あなたが選択</span>
                            @endif
                        </p>
                        @if ($option->option_image_path)
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $option->option_image_path) }}" alt="選択肢画像" class="max-h-48 rounded-md border border-gray-200">
                            </div>
                        @endif
                        @if ($option->option_explanation)
                            <p class="text-sm text-gray-700 mt-2">解説: {!! nl2br(e($option->option_explanation)) !!}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($overall_explanation)
                <div class="bg-blue-50 p-4 rounded-md border border-blue-200 mt-6">
                    <p class="font-semibold mb-2">問題全体の解説:</p>
                    <p class="text-gray-800 leading-relaxed">{!! nl2br(e($overall_explanation)) !!}</p>
                </div>
            @else
                <p class="text-gray-600 italic mt-6">問題全体の解説はありません。</p>
            @endif
        </div>

        <div class="mt-6">
            <form action="{{ route('questions.toggle_flag', $question) }}" method="POST">
                @csrf
                <button type="submit" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                    この問題のフラグを更新
                </button>
            </form>
        </div>

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

        <div class="flex justify-center space-x-4 mt-8">
            @if (!$is_finished)
                <form action="{{ route('quiz.next', $exam) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-full text-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105">
                        次の問題へ ({{ $exam->name }})
                    </button>
                </form>
            @else
                <a href="{{ route('quiz.exam_result', $exam) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-6 rounded-full text-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105">
                    試験リザルトへ
                </a>
            @endif

            @if (!auth()->user()?->isStudent())
                <a href="{{ route('quiz.select_exam') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded-full text-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105">
                    他の試験を選択
                </a>

                @if (($quiz_mode ?? 'normal') !== 'random_count')
                    <a href="{{ route('questions.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded-full text-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105">
                        問題管理画面へ
                    </a>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>