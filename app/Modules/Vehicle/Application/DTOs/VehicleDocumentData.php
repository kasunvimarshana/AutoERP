<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\DTOs;

final readonly class VehicleDocumentData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public int $tenantId,
        public ?int $organizationUnitId,
        public int $vehicleId,
        public string $name,
        public string $filePath,
        public ?string $mimeType = null,
        public ?int $size = null,
        public ?string $type = null,
        public ?array $metadata = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int|string $tenantId, int|string $vehicleId, array $data): self
    {
        return new self(
            tenantId: (int) $tenantId,
            organizationUnitId: isset($data['organization_unit_id']) ? (int) $data['organization_unit_id'] : null,
            vehicleId: (int) $vehicleId,
            name: (string) $data['name'],
            filePath: (string) $data['file_path'],
            mimeType: $data['mime_type'] ?? null,
            size: isset($data['size']) ? (int) $data['size'] : null,
            type: $data['type'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
