<x-app-layout>
    <div class="max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-md">
        <h1 class="text-3xl font-bold mb-8 text-center text-blue-700">受験する試験を選択してください</h1>

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if ($exams->isEmpty())
            <p class="text-center text-gray-600 mb-6">まだ試験が登録されていません。まずは問題管理画面で試験と問題を作成してください。</p>
            <div class="text-center">
                <a href="{{ route('exams.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    新しい試験を作成
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($exams as $exam)
                    <a href="{{ route('quiz.index', $exam) }}" class="block p-5 border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:bg-gray-50 transition duration-200 ease-in-out">
                        <h2 class="text-xl font-semibold text-gray-800">{{ $exam->name }}</h2>
                        @if ($exam->description)
                            <p class="text-gray-600 mt-2 text-sm">{!! nl2br(e(Str::limit($exam->description, 100))) !!}</p>
                        @endif
                        <p class="text-blue-500 mt-3 text-sm font-medium">この試験を開始 &rarr;</p>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mt-8 text-center">
            <a href="{{ route('exams.index') }}" class="text-purple-500 hover:text-purple-800 text-sm">
                試験管理画面へ
            </a>
            <span class="text-gray-400 mx-2">|</span>
            <a href="{{ route('questions.index') }}" class="text-blue-500 hover:text-blue-800 text-sm">
                問題管理画面へ
            </a>
        </div>
    </div>
</x-app-layout>