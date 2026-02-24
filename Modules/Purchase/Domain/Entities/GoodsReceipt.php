<?php
namespace Modules\Purchase\Domain\Entities;
class GoodsReceipt
{
    public function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly string $poId,
        public readonly array $lines,
        public readonly ?string $notes,
        public readonly \DateTimeImmutable $receivedAt,
    ) {}
}
