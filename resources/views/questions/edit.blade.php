<x-app-layout>
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold mb-6 text-center">問題を編集</h1>

        {{-- コントローラーからの汎用エラーメッセージ --}}
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <form action="{{ route('questions.update', $question) }}" method="POST">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">入力内容にエラーがあります。</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <div class="mb-4">
                <label for="exam_id" class="block text-gray-700 text-sm font-bold mb-2">試験を選択:</label>
                <select id="exam_id" name="exam_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('exam_id') border-red-500 @enderror" required>
                    <option value="">試験を選択してください</option>
                    @foreach ($exams as $exam)
                        <option value="{{ $exam->id }}" {{ old('exam_id', $question->exam_id) == $exam->id ? 'selected' : '' }}>
                            {{ $exam->name }}
                        </option>
                    @endforeach
                </select>
                @error('exam_id')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="question_text" class="block text-gray-700 text-sm font-bold mb-2">問題文:</label>
                <textarea id="question_text" name="question_text" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('question_text') border-red-500 @enderror">{{ old('question_text', $question->question_text) }}</textarea>
                @error('question_text')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="difficulty" class="block text-gray-700 text-sm font-bold mb-2">難易度:</label>
                <select id="difficulty" name="difficulty" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('difficulty') border-red-500 @enderror" required>
                    <option value="easy" {{ old('difficulty', $question->difficulty ?? 'normal') === 'easy' ? 'selected' : '' }}>Easy (1pt)</option>
                    <option value="normal" {{ old('difficulty', $question->difficulty ?? 'normal') === 'normal' ? 'selected' : '' }}>Normal (3pt)</option>
                    <option value="expert" {{ old('difficulty', $question->difficulty ?? 'normal') === 'expert' ? 'selected' : '' }}>Expert (5pt)</option>
                </select>
                @error('difficulty')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="overall_explanation" class="block text-gray-700 text-sm font-bold mb-2">問題全体の解説 (任意):</label>
                <textarea id="overall_explanation" name="overall_explanation" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('overall_explanation') border-red-500 @enderror">{{ old('overall_explanation', $question->overall_explanation) }}</textarea>
                @error('overall_explanation')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

            <h2 class="text-xl font-bold mb-4">選択肢の設定</h2>

            @foreach ($question->options as $i => $option)
                <div class="border border-gray-200 p-4 rounded-lg mb-4">
                    <p class="font-semibold mb-2">選択肢 {{ $i + 1 }}</p>
                    <input type="hidden" name="options[{{ $i }}][id]" value="{{ $option->id }}">

                    <div class="mb-2">
                        <label for="option_text_{{ $i }}" class="block text-gray-700 text-sm font-bold mb-1">内容:</label>
                        <input type="text" id="option_text_{{ $i }}" name="options[{{ $i }}][option_text]" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('options.'.$i.'.option_text') border-red-500 @enderror" value="{{ old('options.'.$i.'.option_text', $option->option_text) }}" required>
                        @error('options.'.$i.'.option_text')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="correct_options[{{ $i }}]" value="1" class="form-checkbox text-blue-600" {{ old('correct_options.'.$i, $option->is_correct) ? 'checked' : '' }}>
                            <span class="ml-2 text-gray-700">正解</span>
                        </label>
                    </div>
                    <div>
                        <label for="option_explanation_{{ $i }}" class="block text-gray-700 text-sm font-bold mb-1">解説 (任意):</label>
                        <textarea id="option_explanation_{{ $i }}" name="options[{{ $i }}][option_explanation]" rows="2" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('options.'.$i.'.option_explanation') border-red-500 @enderror">{{ old('options.'.$i.'.option_explanation', $option->option_explanation) }}</textarea>
                        @error('options.'.$i.'.option_explanation')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endforeach

            {{-- 既存の選択肢が4つ未満の場合、足りない分を追加するためのループ（オプション） --}}
            @for ($i = count($question->options); $i < 4; $i++)
                <div class="border border-gray-200 p-4 rounded-lg mb-4 bg-gray-50">
                    <p class="font-semibold mb-2 text-gray-600">新しい選択肢 {{ $i + 1 }}</p>
                    <div class="mb-2">
                        <label for="option_text_{{ $i }}" class="block text-gray-700 text-sm font-bold mb-1">内容:</label>
                        <input type="text" id="option_text_{{ $i }}" name="options[{{ $i }}][option_text]" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('options.'.$i.'.option_text') border-red-500 @enderror" value="{{ old('options.'.$i.'.option_text') }}">
                        @error('options.'.$i.'.option_text')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="correct_options[{{ $i }}]" value="1" class="form-checkbox text-blue-600" {{ old('correct_options.'.$i) ? 'checked' : '' }}>
                            <span class="ml-2 text-gray-700">正解</span>
                        </label>
                    </div>
                    <div>
                        <label for="option_explanation_{{ $i }}" class="block text-gray-700 text-sm font-bold mb-1">解説 (任意):</label>
                        <textarea id="option_explanation_{{ $i }}" name="options[{{ $i }}][option_explanation]" rows="2" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('options.'.$i.'.option_explanation') border-red-500 @enderror">{{ old('options.'.$i.'.option_explanation') }}</textarea>
                        @error('options.'.$i.'.option_explanation')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endfor
            {{-- フラグ設定 --}}
            <div class="mb-4">
                <label for="is_flagged" class="inline-flex items-center">
                    <input type="checkbox" name="is_flagged" id="is_flagged" value="1"
                        {{ old('is_flagged', $isFlagged) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="ms-2 text-sm text-gray-600">要復習</span>
                </label>
            </div>

            <div class="flex items-center justify-between mt-6">
                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    更新
                </button>
                <a href="{{ session('questions_index_url',route('questions.index')) }}" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                    一覧に戻る
                </a>
            </div>
        </form>
    </div>
</x-app-layout>