<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Game */
class GameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => (float) $this->price,
            'rating' => (float) $this->rating,
            'created_at' => $this->created_at?->toIso8601String(),
            // связанные данные — только если загружены через with()/load()
            'author' => new UserResource($this->whenLoaded('author')),
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            'screenshots' => ScreenshotResource::collection($this->whenLoaded('screenshots')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'comments_count' => $this->whenCounted('comments'),
        ];
    }
}
