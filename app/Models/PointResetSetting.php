<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointResetSetting extends Model
{
    protected $fillable = [
        'reset_interval_days',
        'last_reset_at',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'last_reset_at' => 'datetime',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
