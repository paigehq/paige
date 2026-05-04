<?php

use App\Enums\PermissionAction;
use App\Enums\SpaceVisibility;
use App\Models\Space;
use App\Models\User;
use App\Space\Actions\CreateSpace;
use App\Wiki\Exceptions\SlugExhaustedException;

describe('CreateSpace', function () {
    it('creates a space with correct fields and defaults visibility to private', function () {
        $user = User::factory()->create();

        $space = app(CreateSpace::class)->handle(['name' => 'Team Alpha', 'description' => 'Our team'], $user);

        expect($space->name)->toBe('Team Alpha')
            ->and($space->slug)->toBe('team-alpha')
            ->and($space->description)->toBe('Our team')
            ->and($space->visibility)->toBe(SpaceVisibility::Private)
            ->and($space->owner_id)->toBe($user->id);
    });

    it('creates an admin permission row for the owner', function () {
        $user = User::factory()->create();

        $space = app(CreateSpace::class)->handle(['name' => 'Team Alpha'], $user);

        $this->assertDatabaseHas('permissions', [
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Admin->value,
            'granted' => true,
        ]);
    });

    it('respects an explicit visibility', function () {
        $user = User::factory()->create();

        $space = app(CreateSpace::class)->handle(
            ['name' => 'Public Wiki', 'visibility' => SpaceVisibility::Public],
            $user
        );

        expect($space->visibility)->toBe(SpaceVisibility::Public);
    });

    it('generates a suffixed slug when the base slug is taken', function () {
        Space::factory()->create(['slug' => 'team-alpha']);
        $user = User::factory()->create();

        $space = app(CreateSpace::class)->handle(['name' => 'Team Alpha'], $user);

        expect($space->slug)->toBe('team-alpha-2');
    });

    it('throws SlugExhaustedException when all slug suffixes are exhausted', function () {
        Space::factory()->create(['slug' => 'team-alpha']);
        for ($i = 2; $i <= 10; $i++) {
            Space::factory()->create(['slug' => "team-alpha-{$i}"]);
        }
        $user = User::factory()->create();

        expect(fn () => app(CreateSpace::class)->handle(['name' => 'Team Alpha'], $user))
            ->toThrow(SlugExhaustedException::class);
    });
});
