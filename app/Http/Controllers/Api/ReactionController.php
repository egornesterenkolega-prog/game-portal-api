<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReactionRequest;
use App\Http\Resources\ReactionResource;
use App\Models\Game;
use App\Services\ReactionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReactionController extends Controller
{
    public function __construct(private ReactionService $reactions)
    {
    }

    /** PUT /games/{game}/reaction — поставить или изменить оценку. */
    public function update(StoreReactionRequest $request, Game $game): ReactionResource
    {
        $reaction = $this->reactions->set($request->user(), $game, $request->validated('type'));

        return (new ReactionResource($reaction))->additional([
            'game' => ['id' => $game->id, 'rating' => (float) $game->fresh()->rating],
        ]);
    }

    /** DELETE /games/{game}/reaction — снять оценку. */
    public function destroy(Request $request, Game $game): Response
    {
        $this->reactions->remove($request->user(), $game);

        return response()->noContent();
    }
}
