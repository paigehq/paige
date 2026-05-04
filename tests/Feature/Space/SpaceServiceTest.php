<?php

use App\Enums\PermissionAction;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use App\Space\SpaceService;
use App\Wiki\Exceptions\SlugExhaustedException;

describe('SpaceService::generateSlug', function () {
    it('generates a slug from a name', function () {
        $slug = app(SpaceService::class)->generateSlug('My Team Space');
        expect($slug)->toBe('my-team-space');
    });

    it('adds -2 suffix when base slug is taken', function () {
        Space::factory()->create(['slug' => 'my-team-space']);
        $slug = app(SpaceService::class)->generateSlug('My Team Space');
        expect($slug)->toBe('my-team-space-2');
    });

    it('increments suffix up to -10', function () {
        Space::factory()->create(['slug' => 'my-team-space']);
        Space::factory()->create(['slug' => 'my-team-space-2']);
        $slug = app(SpaceService::class)->generateSlug('My Team Space');
        expect($slug)->toBe('my-team-space-3');
    });

    it('throws SlugExhaustedException when all suffixes -2 through -10 are taken', function () {
        Space::factory()->create(['slug' => 'my-team-space']);
        for ($i = 2; $i <= 10; $i++) {
            Space::factory()->create(['slug' => "my-team-space-{$i}"]);
        }
        expect(fn () => app(SpaceService::class)->generateSlug('My Team Space'))
            ->toThrow(SlugExhaustedException::class);
    });

    it('includes soft-deleted space slugs in collision check', function () {
        $space = Space::factory()->create(['slug' => 'my-team-space']);
        $space->delete();
        $slug = app(SpaceService::class)->generateSlug('My Team Space');
        expect($slug)->toBe('my-team-space-2');
    });

    it('excludes the given space id from the collision check when updating', function () {
        $space = Space::factory()->create(['slug' => 'my-team-space']);
        $slug = app(SpaceService::class)->generateSlug('My Team Space', $space->id);
        expect($slug)->toBe('my-team-space');
    });
});

describe('SpaceService::listForUser', function () {
    it('returns only public spaces for unauthenticated users', function () {
        $public = Space::factory()->public()->create();
        Space::factory()->create();           // private
        Space::factory()->secret()->create(); // secret

        $result = app(SpaceService::class)->listForUser(null);

        expect($result)->toHaveCount(1)
            ->and($result->first()->id)->toBe($public->id);
    });

    it('returns public spaces and spaces the user is a member of', function () {
        $user = User::factory()->create();
        $public = Space::factory()->public()->create();
        $private = Space::factory()->create();
        $secret = Space::factory()->secret()->create();

        Permission::factory()->create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $private->id,
            'action' => PermissionAction::Read,
            'granted' => true,
        ]);

        $result = app(SpaceService::class)->listForUser($user);
        $ids = $result->pluck('id');

        expect($ids)->toContain($public->id)
            ->and($ids)->toContain($private->id)
            ->and($ids)->not->toContain($secret->id);
    });

    it('does not return archived spaces', function () {
        $public = Space::factory()->public()->create();
        $public->delete();

        $result = app(SpaceService::class)->listForUser(null);

        expect($result)->toHaveCount(0);
    });
});
