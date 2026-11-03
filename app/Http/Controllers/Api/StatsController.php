<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function __construct(private StatsService $stats)
    {
    }

    /** GET /stats — статистика портала (администратор). */
    public function overview(): JsonResponse
    {
        return response()->json(['data' => $this->stats->overview()]);
    }
}
