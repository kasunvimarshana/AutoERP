<?php

declare(strict_types=1);

namespace Modules\HR\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * DTO for partial department updates.
 *
 * All fields are nullable so that absent keys can be distinguished from
 * intentionally-null values. The isProvided() helper tells the service
 * layer whether a field was explicitly included in the incoming payload,
 * enabling safe partial updates without accidentally clearing existing data.
 * The custom toArray() only emits keys that were explicitly supplied, so
 * downstream array_key_exists checks remain valid after serialisation.
 */
class UpdateDepartmentData extends BaseDto
{
    /** @var list<string> Property names that were explicitly present in the source array. */
    private array $providedKeys = [];

    public ?int $id = null;

    public ?string $name = null;

    public ?string $code = null;

    public ?string $description = null;

    public ?int $manager_id = null;

    public ?int $parent_id = null;

    public ?array $metadata = null;

    public ?bool $is_active = null;

    /**
     * Track which known property names were present in the source array before
     * delegating to the parent fill logic.
     */
    public function fill(array $data): static
    {
        $known = ['id', 'name', 'code', 'description', 'manager_id', 'parent_id', 'metadata', 'is_active'];
        $this->providedKeys = array_values(array_intersect(array_keys($data), $known));

        return parent::fill($data);
    }

    /**
     * Return only the keys that were explicitly provided in the source data.
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
            'manager_id'  => 'nullable|integer',
            'parent_id'   => 'nullable|integer',
            'metadata'    => 'nullable|array',
            'is_active'   => 'boolean',
        ];
    }
}
