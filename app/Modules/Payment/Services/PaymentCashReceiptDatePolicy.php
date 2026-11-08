<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Configuration\Constants\ConfigurationKey;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Payment\Constants\PaymentPermission;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentLine;

final class PaymentCashReceiptDatePolicy
{
    public function __construct(
        private readonly ConfigurationResolverInterface $configuration,
        private readonly PermissionCheckerInterface $permissions,
    ) {}

    public function assertCanPost(Payment $payment, ?int $postedBy): void
    {
        if ($postedBy === null || $payment->direction !== PaymentDirection::Inbound) {
            return;
        }

        $hasCash = $payment->lines->contains(fn (PaymentLine $line): bool => (string) $line->payment_method_type_snapshot === PaymentMethodType::Cash->value);
        if (! $hasCash) {
            return;
        }

        $timezone = (string) $this->configuration->value(
            ConfigurationKey::WORKSPACE_TIMEZONE,
            (int) $payment->tenant_id,
            $payment->organization_unit_id,
        );
        $today = CarbonImmutable::now($timezone)->toDateString();
        $paymentDate = $payment->payment_date->toDateString();
        if ($paymentDate > $today) {
            throw new InvalidArgumentException('Cash receipt date cannot be in the future.');
        }
        if ($paymentDate < $today && ! $this->permissions->allows($postedBy, (int) $payment->tenant_id, PaymentPermission::PAYMENTS_BACKDATE_CASH)) {
            throw new AuthorizationException('Backdated cash receipts require the backdate-cash permission.');
        }
        $isServiceReceipt = $payment->payment_type === PaymentType::ServiceReceipt;
        $reason = $isServiceReceipt
            ? ($payment->metadata['backdate_reason'] ?? null)
            : $payment->notes;
        if ($paymentDate < $today && trim((string) $reason) === '') {
            $field = $isServiceReceipt ? 'backdate_reason' : 'notes';
            throw ValidationException::withMessages([$field => ['A reason is required for a backdated cash receipt.']]);
        }
    }
}
