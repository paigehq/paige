<?php

namespace App\Http\Controllers\Space;

use App\Enums\PermissionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Space\StoreGroupRequest;
use App\Http\Requests\Space\UpdateGroupPermissionRequest;
use App\Models\Space;
use App\Models\User;
use App\Models\UserGroup;
use App\Permission\Exceptions\PermissionDeniedException;
use App\Permission\PermissionChecker;
use App\Space\Actions\AddGroupMember;
use App\Space\Actions\CreateSpaceGroup;
use App\Space\Actions\DeleteSpaceGroup;
use App\Space\Actions\RemoveGroupMember;
use App\Space\Actions\UpdateGroupPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SpaceGroupController extends Controller
{
    public function __construct(
        protected readonly PermissionChecker $checker,
        protected readonly CreateSpaceGroup $createGroup,
        protected readonly DeleteSpaceGroup $deleteGroup,
        protected readonly UpdateGroupPermission $updatePermission,
        protected readonly AddGroupMember $addGroupMember,
        protected readonly RemoveGroupMember $removeGroupMember,
    ) {
        //
    }

    /**
     * @throws PermissionDeniedException
     */
    public function store(StoreGroupRequest $request, Space $space): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->checker->authorize($user, 'admin', $space);

        $this->createGroup->handle($space, $request->string('name')->toString());

        return redirect()->route('spaces.settings.members', $space);
    }

    /**
     * @throws PermissionDeniedException
     */
    public function updatePermission(
        UpdateGroupPermissionRequest $request,
        Space $space,
        UserGroup $group
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $this->checker->authorize($user, 'admin', $space);

        $action = PermissionAction::from($request->string('action')->toString());
        $this->updatePermission->handle($group, $action);

        return redirect()->route('spaces.settings.members', $space);
    }

    /**
     * @throws PermissionDeniedException
     */
    public function destroy(Request $request, Space $space, UserGroup $group): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->checker->authorize($user, 'admin', $space);

        $this->deleteGroup->handle($group);

        return redirect()->route('spaces.settings.members', $space);
    }

    /**
     * @throws PermissionDeniedException
     */
    public function addMember(Request $request, Space $space, UserGroup $group): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->checker->authorize($user, 'admin', $space);

        $member = User::findOrFail($request->integer('user_id'));
        $this->addGroupMember->handle($group, $member);

        return redirect()->route('spaces.settings.members', $space);
    }

    /**
     * @throws PermissionDeniedException
     */
    public function removeMember(Request $request, Space $space, UserGroup $group, User $member): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->checker->authorize($user, 'admin', $space);

        $this->removeGroupMember->handle($group, $member);

        return redirect()->route('spaces.settings.members', $space);
    }
}
