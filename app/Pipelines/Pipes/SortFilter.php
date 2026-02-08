<?php

namespace App\Pipelines\Pipes;

use Closure;

class SortFilter
{
    private array $allowedSortFields = [
        'published_at',
        'created_at',
        'title',
    ];

    public function handle(array $data, Closure $next): mixed
    {
        $query = $data['query'];
        $filters = $data['filters'];

        $sortBy = in_array($filters->sortBy, $this->allowedSortFields)
            ? $filters->sortBy
            : 'published_at';

        $sortDirection = strtolower($filters->sortDirection) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDirection);

        return $next($data);
    }
}
