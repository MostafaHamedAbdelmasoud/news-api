<?php

namespace App\Pipelines\Pipes;

use Closure;

class KeywordFilter
{
    public function handle(array $data, Closure $next): mixed
    {
        $query = $data['query'];
        $filters = $data['filters'];

        if ($filters->keyword) {
            $keyword = $filters->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('content', 'like', "%{$keyword}%")
                    ->orWhere('summary', 'like', "%{$keyword}%");
            });
        }

        return $next($data);
    }
}
