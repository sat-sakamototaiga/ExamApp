<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;

class FlagController extends Controller
{
    public function toggle(Question $question){
        // ログインユーザーのflaggedQuestionsリレーションに対してtoggleを実行
        // 紐づいている場合は解除、紐づいていない場合は追加される
        auth()->user()->flaggedQuestions()->toggle($question->id);
        return back()->with('success', 'フラグの状態を変更しました。');
    }
}
