<?php

namespace App\Pipelines\Pipes;

use Closure;

class SourceFilter
{
    public function handle(array $data, Closure $next): mixed
    {
        $query = $data['query'];
        $filters = $data['filters'];

        if (! empty($filters->sourceIds)) {
            $query->whereIn('source_id', $filters->sourceIds);
        }

        return $next($data);
    }
}
