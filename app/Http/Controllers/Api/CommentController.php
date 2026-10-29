<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Game;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function __construct(private CommentService $comments)
    {
    }

    public function index(Game $game): AnonymousResourceCollection
    {
        return CommentResource::collection($this->comments->paginateForGame($game));
    }

    public function store(StoreCommentRequest $request, Game $game): JsonResponse
    {
        $comment = $this->comments->create($request->user(), $game, $request->validated());

        return (new CommentResource($comment))->response()->setStatusCode(201);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): CommentResource
    {
        return new CommentResource($this->comments->update($comment, $request->validated()));
    }

    public function destroy(Comment $comment): Response
    {
        Gate::authorize('delete', $comment);

        $this->comments->delete($comment);

        return response()->noContent();
    }
}
