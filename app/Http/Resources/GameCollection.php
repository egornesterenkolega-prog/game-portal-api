<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Коллекция игр. При передаче пагинатора ответ содержит data, links и meta
 * (current_page, last_page, per_page, total).
 */
class GameCollection extends ResourceCollection
{
    public $collects = GameResource::class;
}
