<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Data\InvoicePrintContext;
use Modules\Invoice\Enums\InvoiceCopyType;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\User\Contracts\AuthenticatedUserProviderInterface;

final class InvoicePrintIssuanceService
{
    public function __construct(
        private readonly AuthenticatedUserProviderInterface $users,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly DecimalMath $math,
    ) {}

    public function issue(Invoice $invoice): ?InvoicePrintContext
    {
        $type = $invoice->invoice_type instanceof InvoiceType
            ? $invoice->invoice_type
            : InvoiceType::from((string) $invoice->invoice_type);
        if ($type !== InvoiceType::Service) {
            return null;
        }

        $tenantId = (int) $invoice->tenant_id;

        return $this->executionContext->runForTenant(
            $tenantId,
            fn (): ?InvoicePrintContext => DB::transaction(function () use ($invoice, $tenantId): ?InvoicePrintContext {
                $locked = Invoice::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey((int) $invoice->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();
                $balance = $locked->balance()->lockForUpdate()->firstOrFail();

                if ($this->math->compare((string) $balance->paid_amount, '0.000000') <= 0) {
                    return new InvoicePrintContext(
                        new DateTimeImmutable('now', new DateTimeZone('UTC')),
                        $this->printerName(),
                        null,
                    );
                }

                $printedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
                $copyType = $locked->original_printed_at === null
                    ? InvoiceCopyType::Original
                    : InvoiceCopyType::Duplicate;

                if ($copyType === InvoiceCopyType::Original) {
                    $locked->original_printed_at = $printedAt;
                    $locked->save();
                }

                return new InvoicePrintContext($printedAt, $this->printerName(), $copyType);
            }, 3),
        );
    }

    private function printerName(): string
    {
        $user = $this->users->requireCurrentUserRecord();
        $name = trim(implode(' ', array_filter([
            $this->nullableString($user->get('first_name')),
            $this->nullableString($user->get('last_name')),
        ])));

        return $name !== ''
            ? $name
            : $this->nullableString($user->get('username'))
                ?? $this->nullableString($user->get('email'))
                ?? throw new LogicException('The authenticated printer does not have a displayable identity.');
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
