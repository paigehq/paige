@component('mail::message')
    # You've been invited to Paige

    You've been invited to join as a **{{ $role }}**.

    Click the button below to set your password and get started.

    @component('mail::button', ['url' => $acceptUrl])
        Accept Invitation
    @endcomponent

    This invitation expires in 7 days. If you didn't expect this, you can safely ignore it.

    Thanks,<br>
    The Paige Team
@endcomponent
