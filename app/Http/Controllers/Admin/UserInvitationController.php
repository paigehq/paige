<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Actions\InviteUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InviteUserRequest;
use Illuminate\Http\RedirectResponse;

class UserInvitationController extends Controller
{
    public function __construct(protected readonly InviteUser $inviteUser)
    {
        //
    }

    public function store(InviteUserRequest $request): RedirectResponse
    {
        $this->inviteUser->handle(
            $request->validated('email'),
            $request->validated('role'),
            $request->user(),
        );

        return redirect('/admin/users')->with('status', 'Invitation sent.');
    }
}
