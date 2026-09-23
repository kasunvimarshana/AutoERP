<?php

declare(strict_types=1);

return [
    'code_ttl_minutes' => (int) env('WHATSAPP_VERIFICATION_CODE_TTL_MINUTES', 15),
    'maximum_attempts' => (int) env('WHATSAPP_VERIFICATION_MAXIMUM_ATTEMPTS', 5),
    'rate_limit_per_minute' => (int) env('WHATSAPP_VERIFICATION_RATE_LIMIT_PER_MINUTE', 10),
    'message' => "Hello {recipient_name},\n\nPlease reply to this chat with the AutoERP verification code below:\n{verification_code}\n\nThis code expires in {expires_in_minutes} minutes.",
];
