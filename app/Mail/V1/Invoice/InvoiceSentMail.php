<?php

namespace App\Mail\V1\Invoice;

use App\Models\V1\Invoice\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Invoice $invoice
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $ownershipName = $this->invoice->ownership->name ?? 'Property Management System';
        $subject = __('emails.invoice.sent.subject', [
            'number' => $this->invoice->number,
            'ownership' => $ownershipName,
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
        // Load relationships if not already loaded
        if (!$this->invoice->relationLoaded('contract')) {
            $this->invoice->load('contract.tenant.user');
        }
        if (!$this->invoice->relationLoaded('ownership')) {
            $this->invoice->load('ownership');
        }

        return new Content(
            view: 'emails.v1.invoice.sent',
            with: [
                'invoice' => $this->invoice,
                'contract' => $this->invoice->contract,
                'ownership' => $this->invoice->ownership,
                'tenant' => $this->invoice->contract?->tenant,
                'tenantUser' => $this->invoice->contract?->tenant?->user,
                'invoiceUrl' => $this->getInvoiceUrl(),
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
        // TODO: Add PDF attachment if needed
        return [];
    }

    /**
     * Get invoice URL for tenant.
     */
    private function getInvoiceUrl(): ?string
    {
        $frontendUrl = config('app.frontend_url');
        if ($frontendUrl) {
            return rtrim($frontendUrl, '/') . 'dashboard/invoices/' . $this->invoice->uuid;
        }
        return null;
    }
}

