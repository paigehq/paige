<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public readonly string $acceptUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly string $email,
        public readonly string $rawToken,
        public readonly string $role,
    ) {
        $this->acceptUrl = route('invitations.accept.show', ['token' => $this->rawToken]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve been invited to Paige',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invitation',
        );
    }
}
