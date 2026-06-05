<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            フィードバック履歴
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:text-blue-700">ダッシュボードへ戻る</a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold text-gray-900">担任フィードバック履歴</h3>
                    <p class="mt-1 text-sm text-gray-600">新しいコメント順に表示しています。</p>

                    <div class="mt-4 space-y-3">
                        @forelse ($feedbackComments as $feedback)
                            <div class="rounded-lg border border-gray-200 px-4 py-3">
                                <div class="text-xs text-gray-500">
                                    {{ $feedback->created_at->format('Y-m-d H:i') }} / {{ $feedback->teacher?->name ?? '担任' }}
                                </div>
                                <p class="mt-2 whitespace-pre-wrap text-sm text-gray-800">{{ $feedback->comment }}</p>
                            </div>
                        @empty
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                                フィードバック履歴はまだありません。
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-6">
                        {{ $feedbackComments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
