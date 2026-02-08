<?php

namespace App\Pipelines\Pipes;

use Closure;

class CategoryFilter
{
    public function handle(array $data, Closure $next): mixed
    {
        $query = $data['query'];
        $filters = $data['filters'];

        if (! empty($filters->categoryIds)) {
            $query->whereIn('category_id', $filters->categoryIds);
        }

        return $next($data);
    }
}
