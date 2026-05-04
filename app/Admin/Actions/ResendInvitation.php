<?php

namespace App\Admin\Actions;

use App\Jobs\SendInvitationEmail;
use App\Models\UserInvitation;
use Illuminate\Support\Str;

class ResendInvitation
{
    public function handle(UserInvitation $invitation): void
    {
        $rawToken = Str::random(64);

        $invitation->update([
            'token' => hash('sha256', $rawToken),
            'expires_at' => now()->addDays(7),
        ]);

        SendInvitationEmail::dispatch($invitation->email, $rawToken, $invitation->role);
    }
}
