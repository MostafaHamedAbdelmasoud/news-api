<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Source extends Model
{
    /** @use HasFactory<\Database\Factories\SourceFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'base_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function authors(): HasMany
    {
        return $this->hasMany(Author::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
