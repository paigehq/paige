<?php

namespace App\Http\Middleware;

use App\Enums\SpaceVisibility;
use App\Models\Space;
use App\Permission\PermissionChecker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SpaceMiddleware
{
    public function __construct(protected PermissionChecker $checker)
    {
        //
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Space $space */
        $space = $request->route('space');

        return match ($space->visibility) {
            SpaceVisibility::Public => $next($request),
            SpaceVisibility::Private => $this->handlePrivate($request, $next, $space),
            SpaceVisibility::Secret => $this->handleSecret($request, $next, $space),
        };
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    protected function handlePrivate(Request $request, Closure $next, Space $space): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! $this->checker->can($request->user(), 'read', $space)) {
            abort(403);
        }

        return $next($request);
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    protected function handleSecret(Request $request, Closure $next, Space $space): Response
    {
        if (! $request->user() || ! $this->checker->can($request->user(), 'read', $space)) {
            abort(404);
        }

        return $next($request);
    }
}
