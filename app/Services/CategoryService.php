<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    /**
     * @return Collection<int, Category>
     */
    public function getAll(): Collection
    {
        return Category::query()
            ->orderBy('name')
            ->get();
    }

    public function findById(int $id): ?Category
    {
        return Category::find($id);
    }
}
