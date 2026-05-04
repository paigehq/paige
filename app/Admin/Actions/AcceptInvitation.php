<?php

namespace App\Admin\Actions;

use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Support\Facades\Hash;

class AcceptInvitation
{
    public function handle(UserInvitation $invitation, string $name, string $password): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $invitation->email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole($invitation->role);

        $invitation->update(['accepted_at' => now()]);

        return $user;
    }
}
