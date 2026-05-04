<?php

namespace App\Space\Actions;

use App\Enums\PermissionAction;
use App\Enums\SpaceVisibility;
use App\Exceptions\PlanLimitException;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use App\Space\SpaceService;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateSpace
{
    public function __construct(protected SpaceService $spaceService)
    {
        //
    }

    /**
     * @param  array{name: string, description: ?string, visibility: ?string}  $data
     *
     * @throws Throwable
     */
    public function handle(array $data, User $user): Space
    {
        if ($user->plan === 'free') {
            $spaceCount = Permission::where('subject_type', User::class)
                ->where('subject_id', $user->id)
                ->distinct('space_id')
                ->count('space_id');

            if ($spaceCount >= 1) {
                throw new PlanLimitException('spaces', $user->plan);
            }
        }

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
