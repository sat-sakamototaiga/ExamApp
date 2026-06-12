<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointHistory extends Model
{
    public const EVENT_QUESTION_CORRECT = 'question_correct';
    public const EVENT_PERFECT_BONUS = 'perfect_bonus';
    public const EVENT_MANUAL_RESET = 'manual_reset';
    public const EVENT_AUTO_RESET = 'auto_reset';

    protected $fillable = [
        'user_id',
        'teacher_id',
        'question_id',
        'exam_id',
        'event_type',
        'points_delta',
        'balance_after',
        'notes',
    ];

    private const EVENT_TYPE_LABELS = [
        self::EVENT_QUESTION_CORRECT => '問題正解',
        self::EVENT_PERFECT_BONUS => '全問正解ボーナス',
        self::EVENT_MANUAL_RESET => '手動リセット',
        self::EVENT_AUTO_RESET => '自動リセット',
    ];

    protected function casts(): array
    {
        return [
            'points_delta' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function getEventTypeLabelAttribute(): string
    {
        return self::EVENT_TYPE_LABELS[$this->event_type] ?? $this->event_type;
    }
}