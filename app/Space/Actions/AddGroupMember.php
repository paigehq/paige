<?php

namespace App\Space\Actions;

use App\Models\User;
use App\Models\UserGroup;

class AddGroupMember
{
    public function handle(UserGroup $group, User $user): void
    {
        $group->members()->syncWithoutDetaching([$user->id]);
    }
}
