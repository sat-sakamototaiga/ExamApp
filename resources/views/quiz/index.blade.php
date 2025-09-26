<x-app-layout>
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold mb-6 text-center text-blue-700">{{ $exam->name }}</h1> {{-- 試験名を表示 --}}
        <h2 class="text-2xl font-semibold mb-6 text-center text-gray-700">問題</h2>

        @if ($question)
            <div class="mb-8">
                <p class="text-lg font-semibold mb-4">問題 {{ $question->id }}:</p>
                <p class="text-xl leading-relaxed bg-blue-50 p-4 rounded-md border border-blue-200">{!! nl2br(e($question->question_text)) !!}</p>
            </div>

            <form action="{{ route('quiz.answer', $exam) }}" method="POST"> {{-- route('quiz.answer', $exam) に変更 --}}
                @csrf
                <input type="hidden" name="question_id" value="{{ $question->id }}">

                <div class="space-y-4 mb-8">
                    @foreach ($shuffled_options as $option)
                        <label class="block cursor-pointer bg-gray-50 hover:bg-gray-100 p-4 rounded-lg border border-gray-200 shadow-sm">
                            <input type="checkbox" name="selected_options[]" value="{{ $option->id }}" class="mr-3 form-checkbox text-blue-600 focus:ring-blue-500">
                            <span class="text-lg text-gray-800">{!! nl2br(e($option->option_text)) !!}</span>
                        </label>
                    @endforeach
                </div>

                <div class="text-center">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-full text-xl shadow-lg transition duration-300 ease-in-out transform hover:scale-105">
                        解答する
                    </button>
                </div>
            </form>
        @else
            <p class="text-center text-gray-600">この試験には現在、出題できる問題がありません。</p>
            <div class="mt-8 text-center">
                <a href="{{ route('questions.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    新しい問題を作成する
                </a>
            </div>
        @endif

        <div class="mt-10 text-center">
            <a href="{{ route('quiz.select_exam') }}" class="text-blue-500 hover:text-blue-800 text-sm">
                他の試験を選択する
            </a>
            <span class="text-gray-400 mx-2">|</span>
            <a href="{{ route('questions.index') }}" class="text-blue-500 hover:text-blue-800 text-sm">
                問題管理画面へ戻る
            </a>
        </div>
    </div>
</x-app-layout>