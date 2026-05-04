<?php

use App\Jobs\SendInvitationEmail;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'editor', 'guard_name' => 'web']);
});

function makeAdminForResend(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

describe('PATCH /admin/users/invitations/{id}/resend', function () {
    it('regenerates the token, resets expiry, and dispatches a new email job', function () {
        Bus::fake();

        $rawToken = Str::random(64);
        $invitation = UserInvitation::factory()->withRawToken($rawToken)->expired()->create([
            'email' => 'waiting@example.com',
        ]);

        $oldToken = $invitation->token;

        $this->actingAs(makeAdminForResend())
            ->patch("/admin/users/invitations/$invitation->id/resend")
            ->assertRedirect('/admin/users');

        $invitation->refresh();
        expect($invitation->token)->not->toBe($oldToken)
            ->and($invitation->expires_at->isAfter(now()->addDays(6)))->toBeTrue();

        Bus::assertDispatched(SendInvitationEmail::class);
    });

    it('returns 404 for a non-existent invitation', function () {
        $this->actingAs(makeAdminForResend())
            ->patch('/admin/users/invitations/9999/resend')
            ->assertNotFound();
    });

    it('returns 403 for non-admin users', function () {
        $invitation = UserInvitation::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patch("/admin/users/invitations/$invitation->id/resend")
            ->assertForbidden();
    });
});
