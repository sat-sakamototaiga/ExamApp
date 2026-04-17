<?php

namespace App\Http\Controllers;

use App\Models\Exam; // Examモデルをインポート
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamController extends Controller
{
    /**
     * 試験一覧を表示
     */
    public function index()
    {
        $exams = Exam::orderBy('id')->paginate(10);
        return view('exams.index', compact('exams'));
    }

    /**
     * 新しい試験の作成フォームを表示
     */
    public function create()
    {
        return view('exams.create');
    }

    /**
     * 新しい試験をデータベースに保存
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:exams,name', // nameはユニーク
            'description' => 'nullable|string',
        ]);

        Exam::create($request->all());

        return redirect()->route('exams.index')->with('success', '試験が正常に登録されました。');
    }

    /**
     * 特定の試験を表示 (今回は使わない可能性あり)
     */
    public function show(Exam $exam)
    {
        return view('exams.show', compact('exam'));
    }

    /**
     * 特定の試験の編集フォームを表示
     */
    public function edit(Exam $exam)
    {
        $this->authorizeExamMutation();

        return view('exams.edit', compact('exam'));
    }

    /**
     * 特定の試験をデータベースで更新
     */
    public function update(Request $request, Exam $exam)
    {
        $this->authorizeExamMutation();

        $request->validate([
            'name' => 'required|string|max:255|unique:exams,name,' . $exam->id, // 更新時は自分自身のnameを除外
            'description' => 'nullable|string',
        ]);

        $exam->update($request->all());

        return redirect()->route('exams.index')->with('success', '試験が正常に更新されました。');
    }

    /**
     * 特定の試験をデータベースから削除
     */
    public function destroy(Exam $exam)
    {
        $this->authorizeExamMutation();

        // ExamモデルにquestionsリレーションのonDelete('cascade')設定があるため、関連する問題も自動的に削除される
        $exam->delete();

        return redirect()->route('exams.index')->with('success', '試験が正常に削除されました。');
    }

    private function authorizeExamMutation(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user?->isAdmin()) {
            abort(403, '試験の編集・削除は管理者のみ実行できます。');
        }
    }
}