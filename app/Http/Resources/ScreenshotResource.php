<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Screenshot */
class ScreenshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // скриншоты хранятся на диске public (нужна команда php artisan storage:link)
            'url' => Storage::disk('public')->url($this->path),
        ];
    }
}
