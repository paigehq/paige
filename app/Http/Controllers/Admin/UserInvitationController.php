<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Actions\InviteUser;
use App\Admin\Actions\ResendInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InviteUserRequest;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\RedirectResponse;

class UserInvitationController extends Controller
{
    public function __construct(
        protected readonly InviteUser $inviteUser,
        protected readonly ResendInvitation $resendInvitation,
    ) {
        //
    }

    public function store(InviteUserRequest $request): RedirectResponse
    {
        /** @var User $invitedBy */
        $invitedBy = $request->user();

        /** @var string $email */
        $email = $request->validated('email');
        /** @var string $role */
        $role = $request->validated('role');

        $this->inviteUser->handle($email, $role, $invitedBy);

        return redirect('/admin/users')->with('status', 'Invitation sent.');
    }

    public function resend(UserInvitation $invitation): RedirectResponse
    {
        $this->resendInvitation->handle($invitation);

        return redirect('/admin/users')->with('status', 'Invitation resent.');
    }
}
