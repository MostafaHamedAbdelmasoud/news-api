<?php

namespace App\Models;

use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Author extends Model
{
    /** @use HasFactory<AuthorFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'source_id',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
