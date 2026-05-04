<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            if ($user->last_active_at === null || $user->last_active_at->diffInMinutes(now()) >= 5) {
                $user->forceFill(['last_active_at' => now()])->saveQuietly();
            }
        }

        return $response;
    }
}
