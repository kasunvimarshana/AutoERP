<?php

declare(strict_types=1);

namespace Modules\Voucher\Services;

use Modules\Finance\Enums\JournalType;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Voucher\Enums\VoucherType;

final class VoucherTypeRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return [
            VoucherType::Receipt->value => [
                'type' => VoucherType::Receipt->value,
                'label' => VoucherType::Receipt->label(),
                'source_module' => 'Payment',
                'source_kind' => 'payment',
                'direction' => PaymentDirection::Inbound->value,
                'source_url_template' => '/payments/{id}',
            ],
            VoucherType::Payment->value => [
                'type' => VoucherType::Payment->value,
                'label' => VoucherType::Payment->label(),
                'source_module' => 'Payment',
                'source_kind' => 'payment',
                'direction' => PaymentDirection::Outbound->value,
                'source_url_template' => '/payments/{id}',
            ],
            VoucherType::Journal->value => [
                'type' => VoucherType::Journal->value,
                'label' => VoucherType::Journal->label(),
                'source_module' => 'Finance',
                'source_kind' => 'finance_journal',
                'journal_types' => [JournalType::General->value],
                'source_url_template' => '/finance/journals/{id}',
            ],
            VoucherType::Contra->value => [
                'type' => VoucherType::Contra->value,
                'label' => VoucherType::Contra->label(),
                'source_module' => 'Finance',
                'source_kind' => 'finance_journal',
                'journal_types' => [JournalType::Contra->value],
                'source_url_template' => '/finance/journals/{id}',
            ],
            VoucherType::Adjustment->value => [
                'type' => VoucherType::Adjustment->value,
                'label' => VoucherType::Adjustment->label(),
                'source_module' => 'Finance',
                'source_kind' => 'finance_journal',
                'journal_types' => [JournalType::Adjustment->value],
                'source_url_template' => '/finance/journals/{id}',
            ],
            VoucherType::OpeningBalance->value => [
                'type' => VoucherType::OpeningBalance->value,
                'label' => VoucherType::OpeningBalance->label(),
                'source_module' => 'Finance',
                'source_kind' => 'finance_journal',
                'journal_types' => [JournalType::Opening->value],
                'source_url_template' => '/finance/journals/{id}',
            ],
            VoucherType::Reversal->value => [
                'type' => VoucherType::Reversal->value,
                'label' => VoucherType::Reversal->label(),
                'source_module' => 'Payment or Finance',
                'source_kind' => 'payment_reversal|finance_journal',
                'journal_types' => [JournalType::Reversal->value],
                'source_url_template' => null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function get(VoucherType $type): array
    {
        return $this->all()[$type->value];
    }
}
