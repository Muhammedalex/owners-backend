<?php

namespace App\Mail\V1\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public $user,
        public string $verificationUrl
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.verify_email.subject', ['app' => config('app.name')]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.v1.auth.verify-email',
            with: [
                'user' => $this->user,
                'verificationUrl' => $this->verificationUrl,
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

    /**
     * Generate verification URL for the user.
     * Returns frontend URL with signed token parameters.
     */
    public static function generateVerificationUrl($user): string
    {
        // Generate signed backend URL to extract signature and expires
        $signedUrl = URL::temporarySignedRoute(
            'v1.auth.verify-email',
            Carbon::now()->addMinutes((int) Config::get('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );
        
        // Parse the signed URL to extract signature and expires
        $parsedUrl = parse_url($signedUrl);
        parse_str($parsedUrl['query'] ?? '', $queryParams);
        
        // Get frontend URL from environment
        $frontendUrl = env('FRONTEND_URL', config('app.url'));
        $frontendUrl = rtrim($frontendUrl, '/');
        
        // Generate hash for verification
        $hash = sha1($user->getEmailForVerification());
        
        // Build frontend URL with id, hash, and signed parameters
        $frontendRoute = '/auth/verify-email/' . $user->getKey() . '/' . $hash;
        $frontendUrlWithParams = $frontendUrl . $frontendRoute . '?' . http_build_query([
            'signature' => $queryParams['signature'] ?? '',
            'expires' => $queryParams['expires'] ?? '',
        ]);
        
        return $frontendUrlWithParams;
    }
}

