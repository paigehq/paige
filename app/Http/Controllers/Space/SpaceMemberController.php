<?php

namespace App\Http\Controllers\Space;

use App\Enums\PermissionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Space\StoreMemberRequest;
use App\Http\Requests\Space\UpdateMemberRequest;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use App\Models\UserGroup;
use App\Permission\Exceptions\PermissionDeniedException;
use App\Permission\PermissionChecker;
use App\Space\Actions\AddSpaceMember;
use App\Space\Actions\RemoveSpaceMember;
use App\Space\Actions\UpdateSpaceMemberPermission;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SpaceMemberController extends Controller
{
    public function __construct(
        protected readonly PermissionChecker $checker,
        protected readonly AddSpaceMember $addMember,
        protected readonly RemoveSpaceMember $removeMember,
        protected readonly UpdateSpaceMemberPermission $updateMember,
    ) {
        //
    }

    /**
     * @throws PermissionDeniedException
     */
    public function index(Space $space): Response
    {
        /** @var User $authUser */
        $authUser = auth()->user();

        $this->checker->authorize($authUser, 'admin', $space);

        $members = Permission::query()
            ->with('subject')
            ->where('subject_type', User::class)
            ->where('space_id', $space->id)
            ->where('granted', true)
            ->get()
            ->map(function ($p) {
                /** @var User $subject */
                $subject = $p->subject;

                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'email' => $subject->email,
                    'action' => $p->action->value,
                ];
            });

        $groups = $space->userGroups()
            ->with('members')
            ->with(['permissions' => fn (Relation $q) => $q->where('space_id', $space->id)])
            ->get()
            ->map(fn (UserGroup $g) => [
                'id' => $g->id,
                'name' => $g->name,
                'action' => $g->permissions->first()?->action->value,
                'members' => $g->members->map(fn (User $m) => ['id' => $m->id, 'name' => $m->name]),
            ]);

        $memberIds = $members->pluck('id');
        $availableUsers = User::query()
            ->whereNotIn('id', $memberIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('spaces/settings/Members', [
            'space' => ['id' => $space->id, 'name' => $space->name, 'slug' => $space->slug],
            'members' => $members,
            'groups' => $groups,
            'availableUsers' => $availableUsers,
        ]);
    }

    /**
     * @throws PermissionDeniedException
     */
    public function store(StoreMemberRequest $request, Space $space): RedirectResponse
    {
        /** @var User $authUser */
        $authUser = $request->user();

        $this->checker->authorize($authUser, 'admin', $space);

        $member = User::findOrFail($request->integer('user_id'));
        $action = PermissionAction::from($request->string('action')->toString());
        $this->addMember->handle($space, $member, $action);

        return redirect()->route('spaces.settings.members', $space);
    }

    /**
     * @throws PermissionDeniedException
     */
    public function update(UpdateMemberRequest $request, Space $space, User $member): RedirectResponse
    {
        /** @var User $authUser */
        $authUser = $request->user();

        $this->checker->authorize($authUser, 'admin', $space);

        $action = PermissionAction::from($request->string('action')->toString());
        $this->updateMember->handle($space, $member, $action);

        return redirect()->route('spaces.settings.members', $space);
    }

    /**
     * @throws PermissionDeniedException
     */
    public function destroy(Request $request, Space $space, User $member): RedirectResponse
    {
        /** @var User $authUser */
        $authUser = $request->user();

        $this->checker->authorize($authUser, 'admin', $space);

        $this->removeMember->handle($space, $member);

        return redirect()->route('spaces.settings.members', $space);
    }
}
