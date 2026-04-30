<?php

namespace App\Space\Actions;

use App\Models\Permission;
use App\Models\Space;
use App\Models\User;

class RemoveSpaceMember
{
    public function handle(Space $space, User $member): void
    {
        Permission::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $member->id)
            ->where('space_id', $space->id)
            ->delete();
    }
}
