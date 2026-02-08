<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPreference;

class UserPreferenceService
{
    public function getOrCreate(User $user): UserPreference
    {
        return UserPreference::query()
            ->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'preferred_sources' => [],
                    'preferred_categories' => [],
                    'preferred_authors' => [],
                ]
            );
    }

    public function update(User $user, array $data): UserPreference
    {
        return UserPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );
    }
}
