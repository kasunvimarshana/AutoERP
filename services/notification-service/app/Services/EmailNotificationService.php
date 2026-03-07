<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    public function sendEmail(string $to, string $subject, string $content, string $template = 'default'): bool
    {
        try {
            Log::channel('stack')->info('EMAIL SENT', [
                'to' => $to, 'subject' => $subject, 'template' => $template,
            ]);
            // Real integration: Mail::to($to)->send(new \App\Mail\OrderConfirmedMail($data));
            return true;
        } catch (\Throwable $e) {
            Log::error('Email send failed', ['to'=>$to,'error'=>$e->getMessage()]);
            return false;
        }
    }
}
