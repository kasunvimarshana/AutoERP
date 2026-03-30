<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * DTO for partial organization-unit updates.
 *
 * All fields are nullable so that absent keys can be distinguished from
 * intentionally-null values. The isProvided() helper tells the service
 * layer whether a field was explicitly included in the incoming payload,
 * enabling safe partial updates without accidentally clearing existing data.
 * The custom toArray() only emits keys that were explicitly supplied, so
 * downstream array_key_exists checks remain valid after serialisation.
 */
class UpdateOrganizationUnitData extends BaseDto
{
    /** @var list<string> Property names that were explicitly present in the source array. */
    private array $providedKeys = [];

    public ?int $id = null;

    public ?string $name = null;

    public ?string $code = null;

    public ?string $description = null;

    public ?array $metadata = null;

    public ?int $parent_id = null;

    /**
     * Track which known property names were present in the source array before
     * delegating to the parent fill logic.
     */
    public function fill(array $data): static
    {
        $known = ['id', 'name', 'code', 'description', 'metadata', 'parent_id'];
        $this->providedKeys = array_values(array_intersect(array_keys($data), $known));

        return parent::fill($data);
    }

    /**
     * Return only the keys that were explicitly provided in the source data.
     *
     * This preserves partial-update semantics: absent fields are NOT included,
     * so callers and downstream services can still distinguish "not supplied"
     * from "supplied as null" via array_key_exists or isProvided().
     */
    public function toArray(): array
    {
        $all = parent::toArray();

        return array_intersect_key($all, array_flip($this->providedKeys));
    }

    /**
     * Whether a given field was explicitly included in the source payload.
     */
    public function isProvided(string $field): bool
    {
        return in_array($field, $this->providedKeys, true);
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'metadata'    => 'nullable|array',
            'parent_id'   => 'nullable|integer|exists:organization_units,id',
        ];
    }
}
