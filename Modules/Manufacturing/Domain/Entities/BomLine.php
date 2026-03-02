<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Domain\Entities;

/**
 * Bill of Materials line: one component required to produce the finished good.
 */
class BomLine
{
    public function __construct(
        private readonly int     $id,
        private readonly int     $bomId,
        private readonly int     $componentProductId,
        private readonly ?int    $componentVariantId,
        private readonly string  $quantity,
        private readonly ?string $notes,
    ) {}

    public function getId(): int               { return $this->id; }
    public function getBomId(): int            { return $this->bomId; }
    public function getComponentProductId(): int  { return $this->componentProductId; }
    public function getComponentVariantId(): ?int { return $this->componentVariantId; }
    public function getQuantity(): string      { return $this->quantity; }
    public function getNotes(): ?string        { return $this->notes; }
}
