<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Search\SearchService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(protected readonly SearchService $searchService)
    {
        //
    }

    public function index(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim()->value();

        $limit = min($request->integer('limit', 10), 25);

        $limit = max($limit, 1);

        /** @var User|null $user */
        $user = $request->user('sanctum');

        if ($query === '') {
            return response()->json(['results' => [], 'total' => 0, 'query' => '']);
        }

        try {
            $results = $this->searchService->searchForApi($query, $user, $limit);
        } catch (Exception) {
            return response()->json(
                ['error' => 'Search service temporarily unavailable. Please try again shortly.'],
                503,
            );
        }

        return response()->json([
            'results' => $results,
            'total' => count($results),
            'query' => $query,
        ]);
    }
}
