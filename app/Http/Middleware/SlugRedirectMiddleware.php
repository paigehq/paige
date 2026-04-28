<?php

namespace App\Http\Middleware;

use App\Models\PageSlugHistory;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SlugRedirectMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') &&
            preg_match('#^/s/([^/]+)/([^/]+)$#', $request->getPathInfo(), $matches)) {
            $spaceSlug = $matches[1];
            $pageSlug = $matches[2];

            /** @var string|null $currentSlug */
            $currentSlug = PageSlugHistory::query()
                ->join('pages', 'page_slug_history.page_id', '=', 'pages.id')
                ->join('spaces', 'pages.space_id', '=', 'spaces.id')
                ->where('spaces.slug', $spaceSlug)
                ->where('page_slug_history.slug', $pageSlug)
                ->whereNull('pages.deleted_at')
                ->value('pages.slug');

            if ($currentSlug !== null) {
                return redirect("/s/{$spaceSlug}/{$currentSlug}", 301);
            }
        }

        return $next($request);
    }
}
