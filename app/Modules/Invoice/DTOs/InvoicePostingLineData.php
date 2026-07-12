<?php

declare(strict_types=1);

namespace Modules\Invoice\DTOs;

use InvalidArgumentException;
use Modules\Finance\Enums\FinanceAccountRoleCode;

final readonly class InvoicePostingLineData
{
    public function __construct(
        public FinanceAccountRoleCode $role,
        public string $debit = '0.000000',
        public string $credit = '0.000000',
        public ?string $description = null,
        public ?string $sourceLineType = null,
        public ?int $sourceLineId = null,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'role' => $this->role->value,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'description' => $this->description,
            'source_line_type' => $this->sourceLineType,
            'source_line_id' => $this->sourceLineId,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $role = $data['role'] ?? null;
        if (! is_string($role) || FinanceAccountRoleCode::tryFrom($role) === null) {
            throw new InvalidArgumentException('Invoice posting plan line has an invalid Finance role.');
        }

        $sourceLineId = $data['source_line_id'] ?? null;
        if ($sourceLineId !== null && (! is_numeric($sourceLineId) || (int) $sourceLineId < 1)) {
            throw new InvalidArgumentException('Invoice posting plan source line ID must be positive.');
        }

        return new self(
            role: FinanceAccountRoleCode::from($role),
            debit: (string) ($data['debit'] ?? '0.000000'),
            credit: (string) ($data['credit'] ?? '0.000000'),
            description: isset($data['description']) ? (string) $data['description'] : null,
            sourceLineType: isset($data['source_line_type']) ? (string) $data['source_line_type'] : null,
            sourceLineId: $sourceLineId === null ? null : (int) $sourceLineId,
        );
    }
}
