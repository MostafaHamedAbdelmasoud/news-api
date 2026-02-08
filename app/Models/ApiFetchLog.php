<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiFetchLog extends Model
{
    /** @use HasFactory<\Database\Factories\ApiFetchLogFactory> */
    use HasFactory;

    protected $fillable = [
        'source',
        'status',
        'articles_fetched',
        'articles_created',
        'articles_updated',
        'error_message',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'fetched_at' => 'datetime',
            'articles_fetched' => 'integer',
            'articles_created' => 'integer',
            'articles_updated' => 'integer',
        ];
    }
}
