<?php

namespace App\Admin\Actions;

use App\Exceptions\PlanLimitException;
use App\Jobs\SendInvitationEmail;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Str;

class InviteUser
{
    public function handle(string $email, string $role, User $invitedBy): UserInvitation
    {
        if ($invitedBy->plan === 'free') {
            $userCount = User::whereNull('deactivated_at')->count();

            if ($userCount >= 3) {
                throw new PlanLimitException('users', $invitedBy->plan);
            }
        }

        $rawToken = Str::random(64);

        $invitation = UserInvitation::create([
            'email' => $email,
            'role' => $role,
            'token' => hash('sha256', $rawToken),
            'invited_by' => $invitedBy->id,
            'expires_at' => now()->addDays(7),
        ]);

        SendInvitationEmail::dispatch($email, $rawToken, $role);

        return $invitation;
    }
}
