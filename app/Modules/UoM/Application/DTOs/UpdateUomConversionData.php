<?php

declare(strict_types=1);

namespace Modules\UoM\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * DTO for partial UomConversion updates.
 */
class UpdateUomConversionData extends BaseDto
{
    /** @var list<string> */
    private array $providedKeys = [];

    public ?int $id = null;

    public ?int $fromUomId = null;

    public ?int $toUomId = null;

    public ?float $factor = null;

    public ?bool $isActive = null;

    public function fill(array $data): static
    {
        $known = ['id', 'fromUomId', 'toUomId', 'factor', 'isActive'];
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
            'fromUomId' => 'sometimes|required|integer|exists:units_of_measure,id',
            'toUomId'   => 'sometimes|required|integer|exists:units_of_measure,id',
            'factor'    => 'sometimes|required|numeric|min:0',
            'isActive'  => 'boolean',
        ];
    }
}
