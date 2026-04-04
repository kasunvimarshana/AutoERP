<?php
namespace Modules\GS1\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class GS1Label extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly int $barcodeId,
        public readonly string $labelFormat,
        public readonly string $content,
        public readonly ?int $batchId = null,
        public readonly ?string $serialNumber = null,
        public readonly ?\DateTimeImmutable $generatedAt = null,
    ) {
        parent::__construct($id);
    }
}
