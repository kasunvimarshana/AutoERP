<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
