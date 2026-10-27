<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Http\Resources\GenreResource;
use App\Models\Genre;
use App\Services\GenreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GenreController extends Controller
{
    public function __construct(private GenreService $genres)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return GenreResource::collection($this->genres->all());
    }

    public function show(Genre $genre): GenreResource
    {
        return new GenreResource($genre);
    }

    public function store(StoreGenreRequest $request): JsonResponse
    {
        $genre = $this->genres->create($request->validated());

        return (new GenreResource($genre))->response()->setStatusCode(201);
    }

    public function update(UpdateGenreRequest $request, Genre $genre): GenreResource
    {
        return new GenreResource($this->genres->update($genre, $request->validated()));
    }

    public function destroy(Genre $genre): Response
    {
        $this->genres->delete($genre);

        return response()->noContent();
    }
}
