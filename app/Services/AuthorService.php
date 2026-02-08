<?php

namespace App\Services;

use App\Models\Author;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuthorService
{
    public function getPaginated(?int $sourceId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Author::query()->with('source');

        if ($sourceId !== null) {
            $query->where('source_id', $sourceId);
        }

        return $query
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Author
    {
        return Author::with('source')->find($id);
    }
}
