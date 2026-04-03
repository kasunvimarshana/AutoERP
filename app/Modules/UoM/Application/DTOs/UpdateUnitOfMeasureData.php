<?php

declare(strict_types=1);

namespace Modules\UoM\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * DTO for partial UnitOfMeasure updates.
 */
class UpdateUnitOfMeasureData extends BaseDto
{
    /** @var list<string> */
    private array $providedKeys = [];

    public ?int $id = null;

    public ?int $uomCategoryId = null;

    public ?string $name = null;

    public ?string $code = null;

    public ?string $symbol = null;

    public ?bool $isBaseUnit = null;

    public ?float $factor = null;

    public ?string $description = null;

    public ?bool $isActive = null;

    public function fill(array $data): static
    {
        $known = ['id', 'uomCategoryId', 'name', 'code', 'symbol', 'isBaseUnit', 'factor', 'description', 'isActive'];
        $this->providedKeys = array_values(array_intersect(array_keys($data), $known));

        return parent::fill($data);
    }

    public function toArray(): array
    {
        $all = parent::toArray();

        return array_intersect_key($all, array_flip($this->providedKeys));
    }

    public function isProvided(string $field): bool
    {
        return in_array($field, $this->providedKeys, true);
    }

    public function rules(): array
    {
        return [
            'uomCategoryId' => 'sometimes|required|integer|exists:uom_categories,id',
            'name'          => 'sometimes|required|string|max:255',
            'code'          => 'sometimes|required|string|max:50',
            'symbol'        => 'sometimes|required|string|max:20',
            'isBaseUnit'    => 'boolean',
            'factor'        => 'numeric|min:0',
            'description'   => 'nullable|string',
            'isActive'      => 'boolean',
        ];
    }
}
