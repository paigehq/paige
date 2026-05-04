<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'editor', 'guard_name' => 'web']);
    Role::create(['name' => 'viewer', 'guard_name' => 'web']);
});

function makeAdminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

describe('GET /admin/users', function () {
    it('redirects unauthenticated users to login', function () {
        $this->get('/admin/users')->assertRedirect(route('login'));
    });

    it('returns 403 for users with the viewer role', function () {
        $viewer = User::factory()->create();
        $viewer->assignRole('viewer');

        $this->actingAs($viewer)
            ->get('/admin/users')
            ->assertForbidden();
    });

    it('returns 200 for admin users and renders the user index page', function () {
        User::factory()->count(3)->create();

        $this->actingAs(makeAdminUser())
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/users/Index'));
    });

    it('searches users by name', function () {
        User::factory()->create(['name' => 'Alice Smith']);
        User::factory()->create(['name' => 'Bob Jones']);

        $this->actingAs(makeAdminUser())
            ->get('/admin/users?search=alice')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/users/Index')
                ->where('users.data', fn ($data) => collect($data)->pluck('name')->contains('Alice Smith') &&
                    ! collect($data)->pluck('name')->contains('Bob Jones')
                )
            );
    });
});

describe('GET /admin/users/{user}', function () {
    it('renders the user detail page', function () {
        $user = User::factory()->create();

        $this->actingAs(makeAdminUser())
            ->get("/admin/users/$user->id")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/users/Detail'));
    });
});

describe('PATCH /admin/users/{user}/role', function () {
    it('updates the user application role', function () {
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $this->actingAs(makeAdminUser())
            ->patch("/admin/users/$user->id/role", ['role' => 'editor'])
            ->assertRedirect("/admin/users/$user->id");

        expect($user->fresh()->hasRole('editor'))->toBeTrue()
            ->and($user->fresh()->hasRole('viewer'))->toBeFalse();
    });

    it('rejects an invalid role name', function () {
        $user = User::factory()->create();

        $this->actingAs(makeAdminUser())
            ->patch("/admin/users/$user->id/role", ['role' => 'superuser'])
            ->assertSessionHasErrors('role');
    });
});

describe('DELETE /admin/users/{user}', function () {
    it('deactivates the user and redirects to the user list', function () {
        $user = User::factory()->create();

        $this->actingAs(makeAdminUser())
            ->delete("/admin/users/$user->id")
            ->assertRedirect('/admin/users');

        expect($user->fresh()->isDeactivated())->toBeTrue();
    });

    it('does not allow an admin to deactivate themselves', function () {
        $admin = makeAdminUser();

        $this->actingAs($admin)
            ->delete("/admin/users/$admin->id")
            ->assertForbidden();
    });
});
