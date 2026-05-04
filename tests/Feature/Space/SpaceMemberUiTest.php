<?php

use App\Enums\PermissionAction;
use App\Enums\SpaceVisibility;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use App\Models\UserGroup;
use App\Wiki\Actions\PublishPage;

function makeSpaceAdmin(Space $space): User
{
    $admin = User::factory()->create();
    Permission::create([
        'subject_type' => User::class,
        'subject_id' => $admin->id,
        'space_id' => $space->id,
        'action' => PermissionAction::Admin,
        'granted' => true,
    ]);

    return $admin;
}

describe('Members index', function (): void {
    it('space admin can view the members page', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $admin = makeSpaceAdmin($space);

        $this->actingAs($admin)
            ->get(route('spaces.settings.members', $space))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('spaces/settings/Members'));
    });

    it('write user cannot view the members page', function (): void {
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
            ->get(route('spaces.settings.members', $space))
            ->assertForbidden();
    });
});

describe('Adding members', function (): void {
    it('admin can add a user to the space', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $admin = makeSpaceAdmin($space);
        $newUser = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('spaces.settings.members.store', $space), [
                'user_id' => $newUser->id,
                'action' => 'write',
            ])
            ->assertRedirect(route('spaces.settings.members', $space));

        $this->assertDatabaseHas('permissions', [
            'subject_type' => User::class,
            'subject_id' => $newUser->id,
            'space_id' => $space->id,
            'action' => 'write',
            'granted' => true,
        ]);
    });
});

describe('Removing members', function (): void {
    it('admin can remove a user from the space', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $admin = makeSpaceAdmin($space);
        $member = User::factory()->create();
        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $member->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('spaces.settings.members.destroy', [$space, $member]))
            ->assertRedirect(route('spaces.settings.members', $space));

        $this->assertDatabaseMissing('permissions', [
            'subject_type' => User::class,
            'subject_id' => $member->id,
            'space_id' => $space->id,
        ]);
    });
});

describe('Groups', function (): void {
    it('admin can create a group', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $admin = makeSpaceAdmin($space);

        $this->actingAs($admin)
            ->post(route('spaces.settings.groups.store', $space), ['name' => 'Editors'])
            ->assertRedirect(route('spaces.settings.members', $space));

        $this->assertDatabaseHas('user_groups', ['name' => 'Editors', 'space_id' => $space->id]);
    });

    it('group permission grants read access to a private space page', function (): void {
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $editor = User::factory()->create();
        $group = UserGroup::factory()->create(['space_id' => $space->id]);
        $group->members()->attach($editor);
        Permission::create([
            'subject_type' => UserGroup::class,
            'subject_id' => $group->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => true,
        ]);

        $page = Page::factory()->for($space)->for($editor, 'author')->create();
        app(PublishPage::class)->handle($page, $editor);

        $this->actingAs($editor)
            ->get(route('pages.show', [$space, $page->fresh()]))
            ->assertOk();
    });
});
