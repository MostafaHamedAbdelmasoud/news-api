<?php

namespace App\Pipelines\Pipes;

use Closure;

class DateRangeFilter
{
    public function handle(array $data, Closure $next): mixed
    {
        $query = $data['query'];
        $filters = $data['filters'];

        if ($filters->dateFrom) {
            $query->where('published_at', '>=', $filters->dateFrom);
        }

        if ($filters->dateTo) {
            $query->where('published_at', '<=', $filters->dateTo);
        }

        return $next($data);
    }
}
