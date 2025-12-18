<?php

namespace App\Mail\V1\Tenant;

use App\Models\V1\Tenant\TenantInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public TenantInvitation $invitation
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $ownershipName = $this->invitation->ownership->name ?? 'Property Management System';
        $subject = __('emails.tenant_invitation.subject', ['ownership' => $ownershipName]);

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.v1.tenant.invitation',
            with: [
                'invitation' => $this->invitation,
                'ownership' => $this->invitation->ownership,
                'invitationUrl' => $this->invitation->getInvitationUrl(),
                'expiresAt' => $this->invitation->expires_at->format('Y-m-d H:i'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
