<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string  $orderId,
        public readonly string  $recipientName,
        public readonly ?string $reason = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Order #{$this->orderId} Cancelled");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order_cancelled',
            with: [
                'orderId'       => $this->orderId,
                'recipientName' => $this->recipientName,
                'reason'        => $this->reason,
            ]
        );
    }
}
