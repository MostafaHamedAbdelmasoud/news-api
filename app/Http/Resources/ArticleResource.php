<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'summary' => $this->summary,
            'url' => $this->url,
            'image_url' => $this->image_url,
            'published_at' => $this->published_at?->toIso8601String(),
            'source' => new SourceResource($this->whenLoaded('source')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'author' => new AuthorResource($this->whenLoaded('author')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
