<?php

namespace App\Space\Actions;

use App\Enums\PermissionAction;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;

class UpdateSpaceMemberPermission
{
    public function handle(Space $space, User $member, PermissionAction $action): Permission
    {
        Permission::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $member->id)
            ->where('space_id', $space->id)
            ->delete();

        return Permission::create([
            'subject_type' => User::class,
            'subject_id' => $member->id,
            'space_id' => $space->id,
            'action' => $action,
            'granted' => true,
        ]);
    }
}
