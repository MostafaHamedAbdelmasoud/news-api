<?php

namespace App\Services;

use App\Models\Source;
use Illuminate\Database\Eloquent\Collection;

class SourceService
{
    /**
     * @return Collection<int, Source>
     */
    public function getActive(): Collection
    {
        return Source::query()
            ->active()
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): ?Source
    {
        return Source::find($id);
    }
}
