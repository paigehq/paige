<?php

namespace App\Providers;

use App\Models\User;
use App\Models\UserGroup;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api.search', function (Request $request) {
            /** @var User $user */
            $user = $request->user('sanctum');

            return $request->user('sanctum')
                ? Limit::perMinute(30)->by('user:'.$user->id)
                : Limit::perMinute(10)->by($request->ip());
        });

        Route::model('group', UserGroup::class);
        Route::model('member', User::class);

        $this->configureDefaults();
    }

    /**
     * Configure default behaviours for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
