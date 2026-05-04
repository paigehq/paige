<?php

use App\Enums\PermissionAction;
use App\Exceptions\PlanLimitException;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use App\Space\Actions\CreateSpace;

describe('CreateSpace plan limit', function () {
    it('throws PlanLimitException when a free user already has 1 space', function () {
        $user = User::factory()->withPlan('free')->create();
        $existingSpace = Space::factory()->create();
        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $existingSpace->id,
            'action' => PermissionAction::Admin,
            'granted' => true,
        ]);

        expect(fn () => app(CreateSpace::class)->handle(['name' => 'Second Space'], $user))
            ->toThrow(PlanLimitException::class, 'spaces');
    });

    it('allows a free user to create their first space', function () {
        $user = User::factory()->withPlan('free')->create();

        $space = app(CreateSpace::class)->handle(['name' => 'First Space'], $user);

        expect($space->exists)->toBeTrue();
    });

    it('does not enforce the limit for pro users', function () {
        $user = User::factory()->withPlan('pro')->create();

        foreach (range(1, 3) as $i) {
            $space = Space::factory()->create();
            Permission::create([
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'space_id' => $space->id,
                'action' => PermissionAction::Admin,
                'granted' => true,
            ]);
        }

        $newSpace = app(CreateSpace::class)->handle(['name' => 'Fourth Space'], $user);
        expect($newSpace->exists)->toBeTrue();
    });
});
