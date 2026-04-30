<?php

namespace App\Space\Actions;

use App\Models\User;
use App\Models\UserGroup;

class RemoveGroupMember
{
    public function handle(UserGroup $group, User $user): void
    {
        $group->members()->detach($user->id);
    }
}
