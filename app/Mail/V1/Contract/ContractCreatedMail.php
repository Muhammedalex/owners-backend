<?php

namespace App\Mail\V1\Contract;

use App\Models\V1\Contract\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Contract $contract
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $ownershipName = $this->contract->ownership->name ?? 'Property Management System';
        $subject = __('emails.contract.created.subject', [
            'ownership' => $ownershipName,
            'contract_number' => $this->contract->number,
        ]);

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
            view: 'emails.v1.contract.created',
            with: [
                'contract' => $this->contract,
                'ownership' => $this->contract->ownership,
                'tenant' => $this->contract->tenant,
                'statusLabel' => __("contracts.status.{$this->contract->status}"),
                'contractUrl' => config('app.frontend_url') . '/contracts/' . $this->contract->uuid,
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

