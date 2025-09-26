@extends('layouts.app')

@section('title', '問題一覧')

@section('content')
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold mb-6 text-center">問題一覧</h1>

        <div class="mb-6 text-right">
            <a href="{{ route('questions.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                新しい問題を作成
            </a>
            <a href="{{ route('exams.index') }}" class="ml-4 bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                試験を管理
            </a>
        </div>

        @if ($questions->isEmpty())
            <p class="text-center text-gray-600">まだ問題が登録されていません。</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b text-left">ID</th>
                            <th class="py-2 px-4 border-b text-left">試験名</th> {{-- 追加 --}}
                            <th class="py-2 px-4 border-b text-left">問題文</th>
                            <th class="py-2 px-4 border-b text-center">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($questions as $question)
                            <tr>
                                <td class="py-2 px-4 border-b">{{ $question->id }}</td>
                                <td class="py-2 px-4 border-b">{{ $question->exam->name ?? 'N/A' }}</td> {{-- 追加 --}}
                                <td class="py-2 px-4 border-b max-w-xs overflow-hidden text-ellipsis whitespace-nowrap">{!! nl2br(e(Str::limit($question->question_text, 50))) !!}</td>
                                <td class="py-2 px-4 border-b text-center whitespace-nowrap">
                                    <a href="{{ route('questions.edit', $question) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-1 px-3 rounded text-sm mr-2">編集</a>
                                    <form action="{{ route('questions.destroy', $question) }}" method="POST" class="inline-block" onsubmit="return confirm('本当にこの問題を削除しますか？');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm">削除</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $questions->links() }}
            </div>
        @endif
    </div>
@endsection