<?php

namespace App\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class Feature
{
    public static function check(string $feature, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        $allowedPlans = config('features.'.$feature);

        if ($allowedPlans === null) {
            Log::warning("Feature gate check for unknown feature: $feature");

            return false;
        }

        return in_array($user->plan, $allowedPlans, true);
    }
}
