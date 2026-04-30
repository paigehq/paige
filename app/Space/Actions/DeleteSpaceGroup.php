<?php

namespace App\Space\Actions;

use App\Models\Permission;
use App\Models\UserGroup;

class DeleteSpaceGroup
{
    public function handle(UserGroup $group): void
    {
        Permission::query()
            ->where('subject_type', UserGroup::class)
            ->where('subject_id', $group->id)
            ->delete();

        $group->members()->detach();
        $group->delete();
    }
}
