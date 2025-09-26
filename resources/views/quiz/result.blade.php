<x-app-layout>
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold mb-6 text-center @if($is_correct) text-green-700 @else text-red-700 @endif">
            @if($is_correct)
                正解！
            @else
                不正解...
            @endif
        </h1>

        <div class="mb-6">
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

        <div class="flex justify-center space-x-4 mt-8">
            <a href="{{ route('quiz.index', $exam) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-full text-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105">
                次の問題へ ({{ $exam->name }})
            </a>
            <a href="{{ route('quiz.select_exam') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded-full text-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105">
                他の試験を選択
            </a>
            <a href="{{ route('questions.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded-full text-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105">
                問題管理画面へ
            </a>
        </div>
    </div>
</x-app-layout>