<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware('web')->get('/__test/ping', fn () => 'pong');
});

describe('UpdateLastActive middleware', function () {
    it('sets last_active_at on the first authenticated request', function () {
        $user = User::factory()->create(['last_active_at' => null]);

        $this->actingAs($user)->get('/__test/ping');

        expect($user->fresh()->last_active_at)->not->toBeNull();
    });

    it('does not update last_active_at if less than 5 minutes have passed', function () {
        $original = now()->subMinutes(3);
        $user = User::factory()->create(['last_active_at' => $original]);

        $this->actingAs($user)->get('/__test/ping');

        expect($user->fresh()->last_active_at->toDateTimeString())
            ->toBe($original->toDateTimeString());
    });

    it('updates last_active_at if 5 or more minutes have passed', function () {
        $user = User::factory()->create(['last_active_at' => now()->subMinutes(6)]);

        Carbon::setTestNow(now()->addMinute());
        $this->actingAs($user)->get('/__test/ping');
        Carbon::setTestNow(null);

        expect($user->fresh()->last_active_at->isAfter(now()->subMinutes(2)))->toBeTrue();
    });

    it('does nothing for unauthenticated requests', function () {
        $this->get('/__test/ping')->assertOk();
        // No exception thrown — passes if no error
    });
});
