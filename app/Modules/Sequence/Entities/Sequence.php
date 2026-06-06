<?php

declare(strict_types=1);

namespace Modules\Sequence\Entities;

use InvalidArgumentException;
use Modules\Core\Entities\Entity;
use Modules\Sequence\Constants\SequencePeriodType;

final class Sequence extends Entity
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        int|string $id,
        private readonly int $tenantId,
        private readonly ?int $organizationUnitId,
        private readonly string $documentType,
        private readonly int $padding,
        private readonly int $nextNumber,
        private readonly string $periodType,
        private readonly ?string $periodValue,
        private readonly array $metadata = [],
    ) {
        parent::__construct((string) $id);

        if ($this->tenantId < 1) {
            throw new InvalidArgumentException('Tenant id is required.');
        }

        if (trim($this->documentType) === '') {
            throw new InvalidArgumentException('Document type is required.');
        }

        if ($this->padding < 1) {
            throw new InvalidArgumentException('Padding must be at least 1.');
        }

        if ($this->nextNumber < 1) {
            throw new InvalidArgumentException('Next number must be at least 1.');
        }

        if (! SequencePeriodType::isValid($this->periodType)) {
            throw new InvalidArgumentException('Unsupported period type.');
        }
    }

    public function tenantId(): int
    {
        return $this->tenantId;
    }

    public function documentType(): string
    {
        return $this->documentType;
    }

    public function nextNumber(): int
    {
        return $this->nextNumber;
    }
}
