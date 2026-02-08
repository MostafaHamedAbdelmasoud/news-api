<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'summary',
        'url',
        'image_url',
        'source_id',
        'category_id',
        'author_id',
        'published_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
