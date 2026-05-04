<?php

use App\Jobs\SendInvitationEmail;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'editor', 'guard_name' => 'web']);
});

describe('POST /admin/users/invite', function () {
    it('creates a UserInvitation record and queues the email job', function () {
        Bus::fake();

        $this->actingAs(makeAdmin())
            ->post('/admin/users/invite', [
                'email' => 'newuser@example.com',
                'role' => 'editor',
            ])
            ->assertRedirect('/admin/users');

        $this->assertDatabaseHas('user_invitations', [
            'email' => 'newuser@example.com',
            'role' => 'editor',
        ]);

        $invitation = UserInvitation::where('email', 'newuser@example.com')->first();
        expect($invitation->expires_at->isAfter(now()->addDays(6)))->toBeTrue()
            ->and($invitation->accepted_at)->toBeNull();

        Bus::assertDispatched(SendInvitationEmail::class);
    });

    it('returns 422 when the email is already registered', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs(makeAdmin())
            ->post('/admin/users/invite', [
                'email' => 'existing@example.com',
                'role' => 'editor',
            ])
            ->assertSessionHasErrors('email');
    });

    it('returns 422 when role is missing', function () {
        $this->actingAs(makeAdmin())
            ->post('/admin/users/invite', ['email' => 'someone@example.com'])
            ->assertSessionHasErrors('role');
    });

    it('throws PlanLimitException when free installation already has 3 users', function () {
        User::factory()->count(3)->create(['plan' => 'free']);
        $admin = makeAdmin();

        $response = $this->actingAs($admin)
            ->postJson('/admin/users/invite', [
                'email' => 'newuser4@example.com',
                'role' => 'editor',
            ]);

        $response->assertStatus(402);
    });
});
