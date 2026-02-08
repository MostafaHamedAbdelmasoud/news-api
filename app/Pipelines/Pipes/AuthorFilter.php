<?php

namespace App\Pipelines\Pipes;

use Closure;

class AuthorFilter
{
    public function handle(array $data, Closure $next): mixed
    {
        $query = $data['query'];
        $filters = $data['filters'];

        if (! empty($filters->authorIds)) {
            $query->whereIn('author_id', $filters->authorIds);
        }

        return $next($data);
    }
}
