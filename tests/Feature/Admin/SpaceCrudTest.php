<?php

use App\Models\Space;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
});

function makeAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

describe('Admin space index', function () {
    it('rejects unauthenticated users', function () {
        $this->get(route('admin.spaces.index'))->assertRedirect(route('login'));
    });

    it('rejects authenticated users without the admin role', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.spaces.index'))
            ->assertForbidden();
    });

    it('allows admin users and shows all spaces including archived', function () {
        Space::factory()->count(2)->create();
        $archived = Space::factory()->create();
        $archived->delete();

        $this->actingAs(makeAdmin())
            ->get(route('admin.spaces.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/spaces/Index')
                ->has('spaces.data', 3)
            );
    });
});

describe('Admin space create / store', function () {
    it('renders the create page for admins', function () {
        $this->actingAs(makeAdmin())
            ->get(route('admin.spaces.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/spaces/Create'));
    });

    it('creates a space and an owner permission row', function () {
        $admin = makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.spaces.store'), [
                'name' => 'New Space',
                'description' => 'A description',
                'visibility' => 'private',
            ])
            ->assertRedirect(route('admin.spaces.index'));

        $this->assertDatabaseHas('spaces', ['name' => 'New Space', 'slug' => 'new-space']);

        $space = Space::where('slug', 'new-space')->firstOrFail();
        $this->assertDatabaseHas('permissions', [
            'subject_type' => User::class,
            'subject_id' => $admin->id,
            'space_id' => $space->id,
            'action' => 'admin',
            'granted' => true,
        ]);
    });

    it('generates a suffixed slug when the base slug is taken', function () {
        Space::factory()->create(['slug' => 'new-space']);

        $this->actingAs(makeAdmin())
            ->post(route('admin.spaces.store'), [
                'name' => 'New Space',
                'visibility' => 'private',
            ])
            ->assertRedirect(route('admin.spaces.index'));

        $this->assertDatabaseHas('spaces', ['slug' => 'new-space-2']);
    });

    it('validates required fields', function () {
        $this->actingAs(makeAdmin())
            ->post(route('admin.spaces.store'), [])
            ->assertSessionHasErrors(['name', 'visibility']);
    });
});

describe('Admin space edit / update', function () {
    it('renders the edit page for admins', function () {
        $space = Space::factory()->create();

        $this->actingAs(makeAdmin())
            ->get(route('admin.spaces.edit', $space))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/spaces/Edit'));
    });

    it('updates space fields', function () {
        $space = Space::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

        $this->actingAs(makeAdmin())
            ->put(route('admin.spaces.update', $space), [
                'name' => 'New Name',
                'slug' => 'old-name',
                'visibility' => 'public',
            ])
            ->assertRedirect(route('admin.spaces.index'));

        $this->assertDatabaseHas('spaces', ['id' => $space->id, 'name' => 'New Name', 'visibility' => 'public']);
    });

    it('rejects a duplicate slug on update', function () {
        Space::factory()->create(['slug' => 'taken-slug']);
        $space = Space::factory()->create(['slug' => 'my-space']);

        $this->actingAs(makeAdmin())
            ->put(route('admin.spaces.update', $space), [
                'name' => 'My Space',
                'slug' => 'taken-slug',
                'visibility' => 'private',
            ])
            ->assertSessionHasErrors(['slug']);
    });
});

describe('Admin space archive', function () {
    it('archives a space', function () {
        $space = Space::factory()->create();

        $this->actingAs(makeAdmin())
            ->delete(route('admin.spaces.destroy', $space))
            ->assertRedirect(route('admin.spaces.index'));

        expect(Space::find($space->id))->toBeNull()
            ->and(Space::withTrashed()->find($space->id))->not->toBeNull();
    });

    it('archived space is absent from the public listing', function () {
        $space = Space::factory()->public()->create();
        $space->delete();

        $this->get(route('spaces.index'))
            ->assertInertia(fn ($page) => $page->has('spaces', 0));
    });
});
