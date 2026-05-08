<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerEmailVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly \DateTimeInterface $expiresAt,
        public readonly string $customerName,
    ) {
    }

    public function envelope(): Envelope
    {
        $appName = (string) config('app.name', 'Food Delivery');

        return new Envelope(
            subject: 'رمز التحقق من البريد الإلكتروني - ' . $appName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-email-verification',
            with: [
                'appName' => (string) config('app.name', 'Food Delivery'),
                'code' => $this->code,
                'expiresAt' => $this->expiresAt,
                'customerName' => $this->customerName,
            ],
        );
    }
}

