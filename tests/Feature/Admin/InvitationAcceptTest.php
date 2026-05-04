<?php

use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::create(['name' => 'editor', 'guard_name' => 'web']);
});

describe('GET /invitations/{token}/accept', function () {
    it('renders the Accept page for a valid pending invitation', function () {
        $rawToken = Str::random(64);
        UserInvitation::factory()->withRawToken($rawToken)->create([
            'email' => 'invite@example.com',
            'role' => 'editor',
        ]);

        $this->get("/invitations/$rawToken/accept")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('invitations/Accept'));
    });

    it('shows an expired error for an expired invitation', function () {
        $rawToken = Str::random(64);
        UserInvitation::factory()->withRawToken($rawToken)->expired()->create();

        $this->get("/invitations/$rawToken/accept")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('invitations/Accept')
                ->where('expired', true)
            );
    });

    it('returns 404 for an unknown token', function () {
        $this->get('/invitations/not-a-real-token/accept')->assertNotFound();
    });
});

describe('POST /invitations/{token}/accept', function () {
    it('creates a user with the invited role, marks invitation accepted, and redirects to /spaces', function () {
        $rawToken = Str::random(64);
        UserInvitation::factory()->withRawToken($rawToken)->create([
            'email' => 'newperson@example.com',
            'role' => 'editor',
        ]);

        $this->post("/invitations/$rawToken/accept", [
            'name' => 'New Person',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
        ])->assertRedirect('/spaces');

        $user = User::where('email', 'newperson@example.com')->first();
        expect($user)->not->toBeNull()
            ->and($user->hasRole('editor'))->toBeTrue();

        $this->assertDatabaseHas('user_invitations', [
            'email' => 'newperson@example.com',
        ]);
        $invitation = UserInvitation::where('email', 'newperson@example.com')->first();
        expect($invitation->accepted_at)->not->toBeNull();
    });

    it('returns a validation error for a short password', function () {
        $rawToken = Str::random(64);
        UserInvitation::factory()->withRawToken($rawToken)->create();

        $this->post("/invitations/$rawToken/accept", [
            'name' => 'Person',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    });

    it('returns 410 for an expired invitation', function () {
        $rawToken = Str::random(64);
        UserInvitation::factory()->withRawToken($rawToken)->expired()->create();

        $this->post("/invitations/$rawToken/accept", [
            'name' => 'Person',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
        ])->assertStatus(410);
    });
});
