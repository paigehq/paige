<?php

namespace App\Space\Actions;

use App\Models\Space;
use App\Models\UserGroup;
use Illuminate\Support\Str;

class CreateSpaceGroup
{
    public function handle(Space $space, string $name): UserGroup
    {
        return UserGroup::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'space_id' => $space->id,
        ]);
    }
}
