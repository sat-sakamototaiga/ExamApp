<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ようこそ！
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="text-center bg-white p-10 rounded-lg shadow-md">
                <h1 class="text-4xl font-bold text-gray-800 mb-6">資格試験対策アプリへようこそ！</h1>
                <p class="text-lg text-gray-600 mb-8">
                    このアプリで効率的に過去問題に取り組み、合格を目指しましょう。
                    選択肢のランダム化や複数選択問題で、より実践的な学習が可能です。
                </p>
                <div class="space-x-4">
                    @auth
                        {{-- ログインしているユーザー向けのボタン --}}
                        <a href="{{ route('quiz.select_exam') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-full text-xl shadow-lg transition duration-300 ease-in-out transform hover:scale-105">
                            試験を開始する
                        </a>
                    @else
                        {{-- ゲスト（未ログイン）ユーザー向けのボタン --}}
                        <a href="{{ route('login') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-full text-xl shadow-lg transition duration-300 ease-in-out transform hover:scale-105">
                            ログインして開始する
                        </a>
                        @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-full text-xl shadow-lg transition duration-300 ease-in-out transform hover:scale-105">
                            新規登録して開始する
                        </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </div>
</x-app-layout>