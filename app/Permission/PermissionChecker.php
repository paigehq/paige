<?php

namespace App\Permission;

use App\Enums\PermissionAction;
use App\Enums\SpaceVisibility;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use App\Models\UserGroup;
use App\Permission\Exceptions\PermissionDeniedException;

class PermissionChecker
{
    /**
     * Lookup order: explicit user row -> group row -> space default.
     * A granted=false user row for the exact requested action is an absolute deny.
     */
    public function can(User $user, string $action, Space $space): bool
    {
        $satisfying = $this->expandAction($action);

        $userRows = Permission::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('space_id', $space->id)
            ->whereIn('action', $satisfying)
            ->get();

        if ($userRows->isNotEmpty()) {
            if ($userRows->contains(fn (Permission $r) => $r->action->value === $action && ! $r->granted)) {
                return false;
            }

            if ($userRows->contains(fn ($r) => (bool) $r->granted)) {
                return true;
            }
        }

        $groupIds = $user->groups()
            ->where('space_id', $space->id)
            ->pluck('user_groups.id');

        if ($groupIds->isNotEmpty()) {
            $hasGroupGrant = Permission::query()
                ->where('subject_type', UserGroup::class)
                ->whereIn('subject_id', $groupIds)
                ->where('space_id', $space->id)
                ->whereIn('action', $satisfying)
                ->where('granted', true)
                ->exists();

            if ($hasGroupGrant) {
                return true;
            }
        }

        return $this->spaceDefault($action, $space);
    }

    /** @throws PermissionDeniedException */
    public function authorize(User $user, string $action, Space $space): void
    {
        if (! $this->can($user, $action, $space)) {
            throw new PermissionDeniedException;
        }
    }

    /**
     * @return array<PermissionAction>
     */
    protected function expandAction(string $action): array
    {
        return match ($action) {
            'read' => [PermissionAction::Read, PermissionAction::Comment, PermissionAction::Write, PermissionAction::Admin],
            'comment' => [PermissionAction::Comment, PermissionAction::Write, PermissionAction::Admin],
            'write' => [PermissionAction::Write, PermissionAction::Admin],
            'admin' => [PermissionAction::Admin],
            default => [],
        };
    }

    protected function spaceDefault(string $action, Space $space): bool
    {
        if ($space->visibility === SpaceVisibility::Public) {
            return $action !== 'admin';
        }

        return false;
    }
}
