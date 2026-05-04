<?php

use App\Admin\Actions\DeactivateUser;
use App\Enums\PermissionAction;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::create(['name' => 'editor', 'guard_name' => 'web']);
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
});

describe('DeactivateUser action', function () {
    it('sets deactivated_at on the user', function () {
        $user = User::factory()->create();

        app(DeactivateUser::class)->handle($user);

        expect($user->fresh()->isDeactivated())->toBeTrue();
    });

    it('clears remember_token', function () {
        $user = User::factory()->create(['remember_token' => 'some-token']);

        app(DeactivateUser::class)->handle($user);

        expect($user->fresh()->remember_token)->toBeNull();
    });

    it('removes all space permission rows for the user', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create();
        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => true,
        ]);

        app(DeactivateUser::class)->handle($user);

        expect(
            Permission::where('subject_type', User::class)
                ->where('subject_id', $user->id)
                ->count()
        )->toBe(0);
    });

    it('removes application roles from the user', function () {
        $user = User::factory()->create();
        $user->assignRole('editor');

        app(DeactivateUser::class)->handle($user);

        expect($user->fresh()->hasRole('editor'))->toBeFalse();
    });

    it('preserves authored pages by the deactivated user', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create();
        $page = Page::factory()->create([
            'space_id' => $space->id,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        app(DeactivateUser::class)->handle($user);

        $this->assertDatabaseHas('pages', ['id' => $page->id]);
    });

    it('deletes the user sessions', function () {
        $user = User::factory()->create();
        DB::table('sessions')->insert([
            'id' => 'test-session-id',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => 'test',
            'last_activity' => time(),
        ]);

        app(DeactivateUser::class)->handle($user);

        expect(
            DB::table('sessions')->where('user_id', $user->id)->count()
        )->toBe(0);
    });
});
