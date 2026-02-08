<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPreferenceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'preferred_sources' => $this->preferred_sources ?? [],
            'preferred_categories' => $this->preferred_categories ?? [],
            'preferred_authors' => $this->preferred_authors ?? [],
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
