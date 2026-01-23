<?php

namespace App\Modules\AuthManagement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuspiciousActivityDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ?int $userId,
        public string $activityType,
        public array $metadata = []
    ) {}
}
