<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $orderId,
        public readonly string $recipientName,
        public readonly array  $items,
        public readonly float  $totalAmount,
        public readonly string $currency = 'USD'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Order #{$this->orderId} Confirmed");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order_confirmed',
            with: [
                'orderId'       => $this->orderId,
                'recipientName' => $this->recipientName,
                'items'         => $this->items,
                'totalAmount'   => $this->totalAmount,
                'currency'      => $this->currency,
            ]
        );
    }
}
