<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsNotificationService
{
    public function sendSms(string $to, string $message): bool
    {
        try {
            Log::channel('stack')->info('SMS SENT', ['to' => $to, 'message' => $message]);
            // Real integration: Twilio, Nexmo, etc.
            return true;
        } catch (\Throwable $e) {
            Log::error('SMS send failed', ['to'=>$to,'error'=>$e->getMessage()]);
            return false;
        }
    }
}
