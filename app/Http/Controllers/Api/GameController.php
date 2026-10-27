<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexGameRequest;
use App\Http\Requests\StoreGameRequest;
use App\Http\Requests\UpdateGameRequest;
use App\Http\Resources\GameResource;
use App\Models\Game;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GameController extends Controller
{
    public function __construct(private GameService $games)
    {
    }

    public function index(IndexGameRequest $request): AnonymousResourceCollection
    {
        return GameResource::collection($this->games->paginate($request->validated()));
    }

    public function show(Game $game): GameResource
    {
        return new GameResource($this->games->show($game));
    }

    public function store(StoreGameRequest $request): JsonResponse
    {
        $game = $this->games->create(
            $request->user(),
            $request->validated(),
            $request->file('screenshots', []),
            $request->file('game_file'),
        );

        return (new GameResource($game))->response()->setStatusCode(201);
    }

    public function update(UpdateGameRequest $request, Game $game): GameResource
    {
        $game = $this->games->update(
            $game,
            $request->validated(),
            $request->file('screenshots'),
            $request->file('game_file'),
        );

        return new GameResource($game);
    }

    public function destroy(Game $game): Response
    {
        $this->games->delete($game);

        return response()->noContent();
    }
}
