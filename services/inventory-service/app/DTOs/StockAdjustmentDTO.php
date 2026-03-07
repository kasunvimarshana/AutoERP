<?php

namespace App\DTOs;

class StockAdjustmentDTO
{
    /** Adjustment types */
    const TYPE_ADD      = 'add';
    const TYPE_SUBTRACT = 'subtract';
    const TYPE_SET      = 'set';

    public function __construct(
        public readonly string  $type,
        public readonly int     $quantity,
        public readonly string  $reason,
        public readonly ?string $referenceType = null,
        public readonly ?string $referenceId   = null,
        public readonly ?int    $performedBy   = null,
        public readonly ?array  $metadata      = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type:          (string) $data['type'],
            quantity:      (int) $data['quantity'],
            reason:        (string) $data['reason'],
            referenceType: $data['reference_type'] ?? null,
            referenceId:   isset($data['reference_id']) ? (string) $data['reference_id'] : null,
            performedBy:   isset($data['performed_by']) ? (int) $data['performed_by'] : null,
            metadata:      $data['metadata'] ?? null,
        );
    }

    public function isAdd(): bool
    {
        return $this->type === self::TYPE_ADD;
    }

    public function isSubtract(): bool
    {
        return $this->type === self::TYPE_SUBTRACT;
    }

    public function isSet(): bool
    {
        return $this->type === self::TYPE_SET;
    }
}
