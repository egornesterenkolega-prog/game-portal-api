<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isSelfOrAdmin = $viewer && ($viewer->id === $this->id || $viewer->isAdmin());

        // password и remember_token в ответ не попадают никогда
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role->value,
            'email' => $this->when($isSelfOrAdmin, $this->email),
            'is_blocked' => $this->when($isSelfOrAdmin, $this->is_blocked),
            'games' => GameResource::collection($this->whenLoaded('games')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
