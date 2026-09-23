<?php

declare(strict_types=1);

namespace Modules\Core\Data;

final readonly class WhatsAppVerificationRecipient
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $source,
        public ?int $contactId = null,
    ) {}
}
