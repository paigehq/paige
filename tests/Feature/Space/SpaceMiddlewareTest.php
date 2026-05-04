<?php

use App\Enums\PermissionAction;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use App\Models\UserGroup;

describe('SpaceMiddleware', function () {
    it('allows unauthenticated access to a public space', function () {
        $space = Space::factory()->public()->create();

        $this->get("/s/$space->slug")->assertOk();
    });

    it('redirects unauthenticated user to login for a private space', function () {
        $space = Space::factory()->create(); // default: private

        $this->get("/s/$space->slug")->assertRedirect(route('login'));
    });

    it('returns 403 for an authenticated user who is not a member of a private space', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get("/s/$space->slug")->assertForbidden();
    });

    it('allows a member to access a private space', function () {
        $space = Space::factory()->create();
        $user = User::factory()->create();

        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Read,
            'granted' => true,
        ]);

        $this->actingAs($user)->get("/s/$space->slug")->assertOk();
    });

    it('returns 404 for an unauthenticated user accessing a secret space', function () {
        $space = Space::factory()->secret()->create();

        $this->get("/s/$space->slug")->assertNotFound();
    });

    it('returns 404 for an authenticated non-member of a secret space', function () {
        $space = Space::factory()->secret()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get("/s/$space->slug")->assertNotFound();
    });

    it('allows a member to access a secret space', function () {
        $space = Space::factory()->secret()->create();
        $user = User::factory()->create();

        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Read,
            'granted' => true,
        ]);

        $this->actingAs($user)->get("/s/$space->slug")->assertOk();
    });

    it('returns 404 for any user accessing an archived space', function () {
        $space = Space::factory()->public()->create();
        $space->delete(); // archive it
        $user = User::factory()->create();

        $this->actingAs($user)->get("/s/$space->slug")->assertNotFound();
        $this->get("/s/$space->slug")->assertNotFound();
    });
});

it('allows a group member to access a private space', function (): void {
    $space = Space::factory()->create(); // default: private
    $user = User::factory()->create();
    $group = UserGroup::factory()->create(['space_id' => $space->id]);
    $group->members()->attach($user);
    Permission::create([
        'subject_type' => UserGroup::class,
        'subject_id' => $group->id,
        'space_id' => $space->id,
        'action' => PermissionAction::Read,
        'granted' => true,
    ]);

    $this->actingAs($user)->get("/s/$space->slug")->assertOk();
});
