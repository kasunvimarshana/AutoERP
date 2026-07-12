<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

use Modules\Payment\Enums\PaymentPostingRole;

final readonly class PaymentPostingPolicyData
{
    public function __construct(
        public string $postingProfileCode,
        public PaymentPostingRole $allocatedRole,
        public PaymentPostingRole $unappliedRole,
        public PaymentPostingRole $allocationTargetRole,
    ) {}
}
