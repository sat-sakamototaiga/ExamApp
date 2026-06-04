<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {
    public const ROLE_ADMIN = 'admin';
    public const ROLE_TEACHER = 'teacher';
    public const ROLE_STUDENT = 'student';

    public const ROLE_LEVELS = [
        self::ROLE_STUDENT => 1,
        self::ROLE_TEACHER => 2,
        self::ROLE_ADMIN => 3,
    ];

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'total_points',
        'points_reset_at',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'points_reset_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function flaggedQuestions(): BelongsToMany {
        return $this->belongsToMany(Question::class, 'flagged_questions');
    }

    public function createdQuestions(): HasMany {
        return $this->hasMany(Question::class, 'created_by');
    }

    public function students(): BelongsToMany {
        return $this->belongsToMany(self::class, 'teacher_student', 'teacher_id', 'student_id')->withTimestamps();
    }

    public function teachers(): BelongsToMany {
        return $this->belongsToMany(self::class, 'teacher_student', 'student_id', 'teacher_id')->withTimestamps();
    }

    public function givenFeedbackComments(): HasMany {
        return $this->hasMany(TeacherFeedbackComment::class, 'teacher_id');
    }

    public function receivedFeedbackComments(): HasMany {
        return $this->hasMany(TeacherFeedbackComment::class, 'student_id');
    }

    public function hasRoleLevel(string $requiredRole): bool {
        $currentLevel = self::ROLE_LEVELS[$this->role] ?? 0;
        $requiredLevel = self::ROLE_LEVELS[$requiredRole] ?? PHP_INT_MAX;

        return $currentLevel >= $requiredLevel;
    }

    public function isAdmin(): bool {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isTeacher(): bool {
        return $this->role === self::ROLE_TEACHER;
    }

    public function isStudent(): bool {
        return $this->role === self::ROLE_STUDENT;
    }
}
