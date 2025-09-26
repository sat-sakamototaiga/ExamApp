@extends('layouts.app')

@section('title', 'ようこそ！')

@section('content')
    <div class="text-center bg-white p-10 rounded-lg shadow-md">
        <h1 class="text-4xl font-bold text-gray-800 mb-6">資格試験対策アプリへようこそ！</h1>
        <p class="text-lg text-gray-600 mb-8">
            このアプリで効率的に過去問題に取り組み、合格を目指しましょう。
            選択肢のランダム化や複数選択問題で、より実践的な学習が可能です。
        </p>
        <div class="space-x-4">
            <a href="{{ route('quiz.select_exam') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-full text-xl shadow-lg transition duration-300 ease-in-out transform hover:scale-105">
                試験を開始する
            </a>
            <a href="{{ route('questions.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-6 rounded-full text-xl shadow-md transition duration-300 ease-in-out transform hover:scale-105">
                問題管理画面へ
            </a>
        </div>
    </div>
@endsection