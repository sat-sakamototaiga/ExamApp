<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // 追加

class Option extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'option_text',
        'option_image_path',
        'is_correct',
        'option_explanation',
    ];

    protected $casts = [
        'is_correct' => 'boolean', // is_correctをboolean型にキャスト
    ];

    /**
     * この選択肢が属する問題を取得する。
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
