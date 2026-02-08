<?php

namespace App\Models;

use Database\Factories\UserPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    /** @use HasFactory<UserPreferenceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'preferred_sources',
        'preferred_categories',
        'preferred_authors',
    ];

    protected function casts(): array
    {
        return [
            'preferred_sources' => 'array',
            'preferred_categories' => 'array',
            'preferred_authors' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
