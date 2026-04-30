<?php

namespace App\Space\Actions;

use App\Enums\PermissionAction;
use App\Models\Permission;
use App\Models\UserGroup;

class UpdateGroupPermission
{
    public function handle(UserGroup $group, PermissionAction $action): Permission
    {
        Permission::query()
            ->where('subject_type', UserGroup::class)
            ->where('subject_id', $group->id)
            ->where('space_id', $group->space_id)
            ->delete();

        return Permission::create([
            'subject_type' => UserGroup::class,
            'subject_id' => $group->id,
            'space_id' => $group->space_id,
            'action' => $action,
            'granted' => true,
        ]);
    }
}
