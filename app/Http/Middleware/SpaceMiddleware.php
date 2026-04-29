<?php

namespace App\Http\Middleware;

use App\Enums\SpaceVisibility;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SpaceMiddleware
{
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

        if (! $this->isMember($request->user(), $space)) {
            abort(403);
        }

        return $next($request);
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    protected function handleSecret(Request $request, Closure $next, Space $space): Response
    {
        if (! $request->user() || ! $this->isMember($request->user(), $space)) {
            abort(404);
        }

        return $next($request);
    }

    protected function isMember(User $user, Space $space): bool
    {
        return Permission::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('space_id', $space->id)
            ->where('granted', true)
            ->exists();
    }
}
