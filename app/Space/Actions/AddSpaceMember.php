<?php

namespace App\Space\Actions;

use App\Enums\PermissionAction;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;

class AddSpaceMember
{
    public function handle(Space $space, User $member, PermissionAction $action): Permission
    {
        return Permission::create([
            'subject_type' => User::class,
            'subject_id' => $member->id,
            'space_id' => $space->id,
            'action' => $action,
            'granted' => true,
        ]);
    }
}
