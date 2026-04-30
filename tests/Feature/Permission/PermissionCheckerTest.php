<?php

use App\Enums\PermissionAction;
use App\Enums\SpaceVisibility;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use App\Models\UserGroup;
use App\Permission\PermissionChecker;

describe('PermissionChecker — user rows', function (): void {
    it('grants when an explicit user row exists with granted=true', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $user = User::factory()->create();
        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => true,
        ]);

        expect((new PermissionChecker)->can($user, 'write', $space))->toBeTrue();
    });

    it('denies when an explicit user row exists with granted=false', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $user = User::factory()->create();
        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => false,
        ]);

        expect((new PermissionChecker)->can($user, 'write', $space))->toBeFalse();
    });

    it('explicit user deny beats a group grant for the same action', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $user = User::factory()->create();
        $group = UserGroup::factory()->create(['space_id' => $space->id]);
        $group->members()->attach($user);

        Permission::create([
            'subject_type' => UserGroup::class,
            'subject_id' => $group->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => true,
        ]);
        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => false,
        ]);

        expect((new PermissionChecker)->can($user, 'write', $space))->toBeFalse();
    });
});

describe('PermissionChecker — action hierarchy', function (): void {
    it('admin permission implicitly grants write, comment, and read', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $user = User::factory()->create();
        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Admin,
            'granted' => true,
        ]);

        $checker = new PermissionChecker;
        expect($checker->can($user, 'admin', $space))->toBeTrue()
            ->and($checker->can($user, 'write', $space))->toBeTrue()
            ->and($checker->can($user, 'comment', $space))->toBeTrue()
            ->and($checker->can($user, 'read', $space))->toBeTrue();
    });

    it('write permission implicitly grants comment and read but not admin', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $user = User::factory()->create();
        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => true,
        ]);

        $checker = new PermissionChecker;
        expect($checker->can($user, 'write', $space))->toBeTrue()
            ->and($checker->can($user, 'comment', $space))->toBeTrue()
            ->and($checker->can($user, 'read', $space))->toBeTrue()
            ->and($checker->can($user, 'admin', $space))->toBeFalse();
    });
});

describe('PermissionChecker — group rows', function (): void {
    it('grants when the user has no individual row but belongs to a group with a grant', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $user = User::factory()->create();
        $group = UserGroup::factory()->create(['space_id' => $space->id]);
        $group->members()->attach($user);
        Permission::create([
            'subject_type' => UserGroup::class,
            'subject_id' => $group->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => true,
        ]);

        expect((new PermissionChecker)->can($user, 'write', $space))->toBeTrue();
    });

    it('denies when the user belongs to a group that has no permission row', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $user = User::factory()->create();
        $group = UserGroup::factory()->create(['space_id' => $space->id]);
        $group->members()->attach($user);

        expect((new PermissionChecker)->can($user, 'write', $space))->toBeFalse();
    });
});

describe('PermissionChecker — space defaults', function (): void {
    it('allows an authenticated user to read a public space with no permission row', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        $user = User::factory()->create();

        expect((new PermissionChecker)->can($user, 'read', $space))->toBeTrue();
    });

    it('allows an authenticated user to write to a public space with no permission row', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        $user = User::factory()->create();

        expect((new PermissionChecker)->can($user, 'write', $space))->toBeTrue();
    });

    it('denies admin on a public space with no permission row', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        $user = User::factory()->create();

        expect((new PermissionChecker)->can($user, 'admin', $space))->toBeFalse();
    });

    it('denies all actions on a private space with no permission rows', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $user = User::factory()->create();
        $checker = new PermissionChecker;

        expect($checker->can($user, 'read', $space))->toBeFalse()
            ->and($checker->can($user, 'write', $space))->toBeFalse()
            ->and($checker->can($user, 'admin', $space))->toBeFalse();
    });
});
