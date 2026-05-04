<?php

namespace App\Http\Controllers;

use App\Admin\Actions\AcceptInvitation;
use App\Http\Requests\AcceptInvitationRequest;
use App\Models\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class InvitationController extends Controller
{
    public function __construct(protected readonly AcceptInvitation $acceptInvitation)
    {
        //
    }

    public function show(string $token): InertiaResponse
    {
        $invitation = UserInvitation::where('token', hash('sha256', $token))->firstOrFail();

        return Inertia::render('invitations/Accept', [
            'email' => $invitation->email,
            'expired' => $invitation->isExpired(),
            'token' => $token,
        ]);
    }

    public function store(AcceptInvitationRequest $request, string $token): RedirectResponse|Response
    {
        $invitation = UserInvitation::where('token', hash('sha256', $token))->firstOrFail();

        if ($invitation->isExpired()) {
            abort(410);
        }

        $user = $this->acceptInvitation->handle(
            $invitation,
            $request->validated('name'),
            $request->validated('password')
        );

        Auth::login($user);

        return redirect('/spaces');
    }
}
