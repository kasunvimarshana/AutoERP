<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\DTOs;

/**
 * Partial-update DTO for attachment classification fields.
 *
 * Only `type` and `metadata` may be updated without replacing the stored file.
 * File path, MIME type, name, size, and UUID are immutable after creation and
 * are therefore intentionally absent from this DTO.
 *
 * Uses the same "tracked-keys" pattern as UpdateOrganizationUnitData:
 *  - fill()       — records which keys were explicitly present in the source array
 *  - isProvided() — lets the service distinguish "absent" from "explicitly null"
 *  - toArray()    — only emits keys that were provided
 *
 * This preserves array_key_exists() semantics as the data flows into the service.
 */
class UpdateOrganizationUnitAttachmentData
{
    public ?string $type     = null;
    public ?array  $metadata = null;

    /** @var array<string, true> */
    private array $provided = [];

    private function __construct() {}

    /**
     * Build a DTO from the validated request payload, recording which keys
     * were explicitly present so that isProvided() works correctly.
     */
    public static function fromArray(array $data): static
    {
        $dto = new static;
        $dto->fill($data);

        return $dto;
    }

    /**
     * Assign values and record which keys were present.
     *
     * @return $this
     */
    public function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key          = $value;
                $this->provided[$key] = true;
            }
        }

        return $this;
    }

    /**
     * Was this field present in the source array?
     */
    public function isProvided(string $field): bool
    {
        return isset($this->provided[$field]);
    }

    /**
     * Return only the keys that were explicitly provided.
     *
     * Absent fields are not emitted, preserving partial-update semantics.
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->provided as $key => $_) {
            $result[$key] = $this->$key;
        }

        return $result;
    }
}
