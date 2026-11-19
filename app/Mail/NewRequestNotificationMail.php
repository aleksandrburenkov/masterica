<?php

namespace App\Mail;

use App\Services\RequestNotifications\RequestNotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewRequestNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly RequestNotificationData $requestData,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->requestData->subject(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.request-notification',
            text: 'emails.request-notification-text',
            with: [
                'data' => $this->requestData,
            ],
        );
    }
}
