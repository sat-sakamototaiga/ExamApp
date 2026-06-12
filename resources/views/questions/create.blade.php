<x-app-layout>
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold mb-6 text-center">新しい問題を作成</h1>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('questions.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <label for="exam_id" class="block text-gray-700 text-sm font-bold mb-2">試験を選択:</label>
                <select id="exam_id" name="exam_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('exam_id') border-red-500 @enderror" required>
                    <option value="">試験を選択してください</option>
                    @foreach ($exams as $exam)
                        <option value="{{ $exam->id }}" {{ old('exam_id') == $exam->id ? 'selected' : '' }}>
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
                <textarea id="question_text" name="question_text" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('question_text') border-red-500 @enderror">{{ old('question_text') }}</textarea>
                @error('question_text')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="question_image" class="block text-gray-700 text-sm font-bold mb-2">問題画像 (任意):</label>
                <input type="file" id="question_image" name="question_image" accept="image/*" class="block w-full text-sm text-gray-700">
                @error('question_image')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="difficulty" class="block text-gray-700 text-sm font-bold mb-2">難易度:</label>
                <select id="difficulty" name="difficulty" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('difficulty') border-red-500 @enderror" required>
                    <option value="easy" {{ old('difficulty', 'normal') === 'easy' ? 'selected' : '' }}>Easy (1pt)</option>
                    <option value="normal" {{ old('difficulty', 'normal') === 'normal' ? 'selected' : '' }}>Normal (3pt)</option>
                    <option value="expert" {{ old('difficulty', 'normal') === 'expert' ? 'selected' : '' }}>Expert (5pt)</option>
                </select>
                @error('difficulty')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="overall_explanation" class="block text-gray-700 text-sm font-bold mb-2">問題全体の解説 (任意):</label>
                <textarea id="overall_explanation" name="overall_explanation" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('overall_explanation') border-red-500 @enderror">{{ old('overall_explanation') }}</textarea>
                @error('overall_explanation')
                    <p class="text-red-500 text-xs italic">{{ $message }}</p>
                @enderror
            </div>

            <h2 class="text-xl font-bold mb-4">選択肢の設定</h2>

            @for ($i = 0; $i < 4; $i++)
                <div class="border border-gray-200 p-4 rounded-lg mb-4">
                    <p class="font-semibold mb-2">選択肢 {{ $i + 1 }}</p>
                    <div class="mb-2">
                        <label for="option_text_{{ $i }}" class="block text-gray-700 text-sm font-bold mb-1">内容:</label>
                        <input type="text" id="option_text_{{ $i }}" name="options[{{ $i }}][option_text]" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('options.'.$i.'.option_text') border-red-500 @enderror" value="{{ old('options.'.$i.'.option_text') }}" required>
                        @error('options.'.$i.'.option_text')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <label for="option_image_{{ $i }}" class="block text-gray-700 text-sm font-bold mb-1">画像 (任意):</label>
                        <input type="file" id="option_image_{{ $i }}" name="options[{{ $i }}][option_image]" accept="image/*" class="block w-full text-sm text-gray-700">
                        @error('options.'.$i.'.option_image')
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
                <label class="is_flagged" class="inline-flex items-center">
                    <input type="checkbox" name="is_flagged" value="1" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-indigo-500">
                    <span class="ml-2 text-gray-600">要復習</span>
                </label>
            </div>
            
            <div class="flex items-center justify-between mt-6">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    登録
                </button>
                <a href="{{ route('questions.index') }}" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                    一覧に戻る
                </a>
            </div>
        </form>
    </div>
</x-app-layout>