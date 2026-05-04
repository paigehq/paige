<?php

use App\Exceptions\PlanLimitException;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    // Stub route that throws PlanLimitException to test the global handler
    Route::middleware('web')->post('/__test/plan-limit', function () {
        throw new PlanLimitException('spaces', 'free');
    });
});

describe('PlanLimitException HTTP handler', function () {
    it('returns 402 with correct JSON body', function () {
        $this->actingAs(User::factory()->create())
            ->postJson('/__test/plan-limit')
            ->assertStatus(402)
            ->assertJson([
                'error' => [
                    'code' => 'plan_limit',
                    'message' => 'Plan limit reached: cannot add more spaces on the free plan.',
                    'upgrade_url' => '/settings/billing',
                ],
            ]);
    });
});
