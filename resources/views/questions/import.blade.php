<x-app-layout>
    @if (session('success'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 4000)"
            x-show="show"
            x-transition
            class="fixed top-6 right-6 z-50 max-w-md w-full"
            role="status"
            aria-live="polite"
        >
            <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded-lg shadow-lg flex items-start justify-between gap-3">
                <div>
                    <p class="font-bold">インポート成功</p>
                    <p class="text-sm">{{ session('success') }}</p>
                </div>
                <button type="button" @click="show = false" class="text-green-700 hover:text-green-900 font-bold" aria-label="閉じる">×</button>
            </div>
        </div>
    @endif

    <div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold mb-6 text-center">問題インポート</h1>
        <div class="mb-4 text-right">
            <a href="{{ route('questions.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-semibold">
                問題一覧へ戻る
            </a>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <p class="font-bold">エラー:</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('questions.import') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- 試験選択のドロップダウンを追加 --}}
            <div class="mb-4">
                <label for="exam_id" class="block text-gray-700 text-sm font-bold mb-2">インポート先の試験を選択:</label>
                <select id="exam_id" name="exam_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('exam_id') border-red-500 @enderror" required>
                    <option value="">試験を選択してください</option>
                    @foreach ($exams as $exam)
                        <option value="{{ $exam->id }}" {{ old('exam_id') == $exam->id ? 'selected' : '' }}>
                            {{ $exam->name }}
                        </option>
                    @endforeach
                </select>
                @error('exam_id')
                    <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="csv_file" class="block text-gray-700 text-sm font-bold mb-2">CSVファイルを選択:</label>
                <input type="file" id="csv_file" name="csv_file" class="block w-full text-sm text-gray-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-blue-50 file:text-blue-700
                    hover:file:bg-blue-100" required>
                @error('csv_file')
                    <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="images_zip" class="block text-gray-700 text-sm font-bold mb-2">画像ZIPファイル（任意）:</label>
                <input type="file" id="images_zip" name="images_zip" accept=".zip" class="block w-full text-sm text-gray-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-blue-50 file:text-blue-700
                    hover:file:bg-blue-100">
                @error('images_zip')
                    <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <p class="text-gray-600 text-sm">
                    CSVファイルは以下の形式で作成してください。<br>
                    1行目はヘッダーとして扱われます。<br>
                    <strong class="text-red-600">※ `正解X` は `1` (正解) または `0` (不正解) で入力してください。</strong><br>
                    ※ `全体解説` および `解説X` は空欄でも構いません。<br>
                    ※ 画像を使う場合は「画像ZIP（任意）」に同名ファイルを含めてください（サブフォルダ不可）。
                </p>
                <div class="mt-3">
                    <a href="{{ route('questions.import.template') }}" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold py-2 px-4 rounded">
                        CSVテンプレートをダウンロード
                    </a>
                </div>
                <code class="block bg-gray-100 p-3 rounded-md text-gray-800 text-xs break-all mt-2">
                    旧形式: 問題文,全体解説,選択肢1,正解1,解説1,選択肢2,正解2,解説2,選択肢3,正解3,解説3,選択肢4,正解4,解説4
                    <br>
                    画像対応形式: 問題文,問題画像,全体解説,選択肢1,選択肢1画像,正解1,解説1,選択肢2,選択肢2画像,正解2,解説2,選択肢3,選択肢3画像,正解3,解説3,選択肢4,選択肢4画像,正解4,解説4
                </code>
            </div>

            <div class="text-center">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline">
                    インポートを実行
                </button>
            </div>
        </form>
    </div>
</x-app-layout>