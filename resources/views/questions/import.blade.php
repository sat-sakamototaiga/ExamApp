@extends('layouts.app')

@section('title', '問題インポート')

@section('content')
    <div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold mb-6 text-center">問題インポート</h1>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <p class="font-bold">エラー:</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{!! $error !!}</li> {{-- HTMLタグが含まれる可能性があるので{!! !!}を使用 --}}
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

            <div class="mb-6">
                <p class="text-gray-600 text-sm">
                    CSVファイルは以下の形式で作成してください。<br>
                    1行目はヘッダーとして扱われます。<br>
                    <strong class="text-red-600">※ `正解X` は `1` (正解) または `0` (不正解) で入力してください。</strong><br>
                    ※ `全体解説` および `解説X` は空欄でも構いません。
                </p>
                <code class="block bg-gray-100 p-3 rounded-md text-gray-800 text-xs break-all mt-2">
                    問題文,全体解説,選択肢1,正解1,解説1,選択肢2,正解2,解説2,選択肢3,正解3,解説3,選択肢4,正解4,解説4
                </code>
            </div>

            <div class="text-center">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline">
                    インポートを実行
                </button>
            </div>
        </form>
    </div>
@endsection