<?php

use App\Feature\Feature;
use App\Models\User;
use Illuminate\Support\Facades\Log;

describe('Feature::check()', function () {
    it('returns false for free user on sso_saml', function () {
        $user = User::factory()->withPlan('free')->create();
        expect(Feature::check('sso_saml', $user))->toBeFalse();
    });

    it('returns true for pro user on sso_saml', function () {
        $user = User::factory()->withPlan('pro')->create();
        expect(Feature::check('sso_saml', $user))->toBeTrue();
    });

    it('returns true for business user on sso_saml', function () {
        $user = User::factory()->withPlan('business')->create();
        expect(Feature::check('sso_saml', $user))->toBeTrue();
    });

    it('returns false for pro user on advanced_analytics', function () {
        $user = User::factory()->withPlan('pro')->create();
        expect(Feature::check('advanced_analytics', $user))->toBeFalse();
    });

    it('returns true for business user on advanced_analytics', function () {
        $user = User::factory()->withPlan('business')->create();
        expect(Feature::check('advanced_analytics', $user))->toBeTrue();
    });

    it('returns false and logs a warning for an unknown feature key', function () {
        Log::shouldReceive('warning')
            ->once()
            ->with(Mockery::pattern('/unknown_feature_xyz/'));

        $user = User::factory()->withPlan('business')->create();
        expect(Feature::check('unknown_feature_xyz', $user))->toBeFalse();
    });

    it('returns false when no user is passed and none is authenticated', function () {
        expect(Feature::check('sso_saml'))->toBeFalse();
    });

    it('uses the authenticated user when no user argument is passed', function () {
        $user = User::factory()->withPlan('pro')->create();
        $this->actingAs($user);
        expect(Feature::check('sso_saml'))->toBeTrue();
    });
});

dataset('feature plan matrix', [
    ['sso_saml', 'free', false],
    ['sso_saml', 'pro', true],
    ['sso_saml', 'business', true],
    ['custom_domain', 'free', false],
    ['custom_domain', 'pro', true],
    ['custom_domain', 'business', true],
    ['audit_log_api', 'free', false],
    ['audit_log_api', 'pro', false],
    ['audit_log_api', 'business', true],
    ['scim_provisioning', 'free', false],
    ['scim_provisioning', 'pro', false],
    ['scim_provisioning', 'business', true],
    ['advanced_analytics', 'free', false],
    ['advanced_analytics', 'pro', false],
    ['advanced_analytics', 'business', true],
]);

it('returns correct value for every feature × plan combination',
    function (string $feature, string $plan, bool $expected) {
        $user = User::factory()->withPlan($plan)->create();
        expect(Feature::check($feature, $user))->toBe($expected);
    })->with('feature plan matrix');
