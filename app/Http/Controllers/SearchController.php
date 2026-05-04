<?php

namespace App\Http\Controllers;

use App\Search\SearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __construct(
        protected readonly SearchService $searchService,
    ) {
        //
    }

    public function index(Request $request): Response
    {
        $query = $request->string('q')->trim()->value();

        return Inertia::render('Search', [
            'query' => $query,
            'results' => $query !== '' ? $this->searchService->search($query, auth()->user()) : [],
        ]);
    }
}
