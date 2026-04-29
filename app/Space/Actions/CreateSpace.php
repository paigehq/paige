<?php

namespace App\Space\Actions;

use App\Enums\PermissionAction;
use App\Enums\SpaceVisibility;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use App\Space\SpaceService;
use Illuminate\Support\Facades\DB;

class CreateSpace
{
    public function __construct(protected SpaceService $spaceService)
    {
        //
    }

    /**
     * @param  array{name: string, description: ?string, visibility: ?string}  $data
     */
    public function handle(array $data, User $user): Space
    {
        return DB::transaction(function () use ($data, $user): Space {
            $space = Space::create([
                'name' => $data['name'],
                'slug' => $this->spaceService->generateSlug((string) $data['name']),
                'description' => $data['description'] ?? null,
                'visibility' => $data['visibility'] ?? SpaceVisibility::Private,
                'owner_id' => $user->id,
            ]);

            Permission::create([
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'space_id' => $space->id,
                'action' => PermissionAction::Admin,
                'granted' => true,
            ]);

            return $space;
        });
    }
}
