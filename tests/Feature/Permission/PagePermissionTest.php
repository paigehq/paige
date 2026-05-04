<?php

use App\Enums\PermissionAction;
use App\Enums\SpaceVisibility;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use App\Models\UserGroup;
use App\Wiki\Actions\PublishPage;

describe('Page write permissions', function (): void {
    it('user with write permission can POST to create a page on a private space', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $user = User::factory()->create();
        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => true,
        ]);

        $this->actingAs($user)
            ->post(route('pages.store', $space), ['title' => 'New Page'])
            ->assertRedirect();

        $this->assertDatabaseHas('pages', ['title' => 'New Page', 'space_id' => $space->id]);
    });

    it('user with read-only permission gets 403 on page store', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $user = User::factory()->create();
        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Read,
            'granted' => true,
        ]);

        $this->actingAs($user)
            ->post(route('pages.store', $space), ['title' => 'Blocked'])
            ->assertForbidden();
    });

    it('PermissionDeniedException produces a 403 not a 500', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $user = User::factory()->create();
        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Read,
            'granted' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('pages.store', $space), ['title' => 'Blocked']);

        expect($response->status())->toBe(403);
    });

    it('explicit user deny beats group grant on page store', function (): void {
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
        // Explicit user deny for write
        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => false,
        ]);
        // Read grant so SpaceMiddleware lets the user through
        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Read,
            'granted' => true,
        ]);

        $this->actingAs($user)
            ->post(route('pages.store', $space), ['title' => 'Denied'])
            ->assertForbidden();
    });

    it('removing a user permission row prevents further write access on a private space', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $user = User::factory()->create();
        $row = Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => true,
        ]);

        $this->actingAs($user)
            ->post(route('pages.store', $space), ['title' => 'Before Removal'])
            ->assertRedirect();

        $row->delete();

        $this->actingAs($user)
            ->post(route('pages.store', $space), ['title' => 'After Removal'])
            ->assertForbidden();
    });

    it('user with admin permission can update a page', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $user = User::factory()->create();
        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Admin,
            'granted' => true,
        ]);
        $page = Page::factory()->for($space)->for($user, 'author')->create();
        app(PublishPage::class)->handle($page, $user);

        $this->actingAs($user)
            ->put(route('pages.update', [$space, $page->fresh()]), [
                'action' => 'publish',
                'title' => 'Updated Title',
                'content' => null,
            ])
            ->assertRedirect();
    });
});
