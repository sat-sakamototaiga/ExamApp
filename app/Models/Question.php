<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Question extends Model {
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'created_by',
        'question_text',
        'question_image_path',
        'difficulty',
        'overall_explanation', // 変更
    ];

    public const DIFFICULTY_EASY = 'easy';
    public const DIFFICULTY_NORMAL = 'normal';
    public const DIFFICULTY_EXPERT = 'expert';

    public const DIFFICULTIES = [
        self::DIFFICULTY_EASY,
        self::DIFFICULTY_NORMAL,
        self::DIFFICULTY_EXPERT,
    ];

    /**
     * この問題が属する試験を取得する。
     */
    public function exam(): BelongsTo {
        return $this->belongsTo(Exam::class);
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * この問題に属する複数の選択肢を取得する。
     */
    public function options(): HasMany {
        return $this->hasMany(Option::class);
    }

    public function flaggedByUsers(): BelongsToMany {
        return $this->belongsToMany(User::class, 'flagged_questions');
    }
}
