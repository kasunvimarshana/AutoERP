<?php

declare(strict_types=1);

namespace Modules\UoM\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

/**
 * DTO for partial ProductUomSetting updates.
 */
class UpdateProductUomSettingData extends BaseDto
{
    /** @var list<string> */
    private array $providedKeys = [];

    public ?int $id = null;

    public ?int $baseUomId = null;

    public ?int $purchaseUomId = null;

    public ?int $salesUomId = null;

    public ?int $inventoryUomId = null;

    public ?float $purchaseFactor = null;

    public ?float $salesFactor = null;

    public ?float $inventoryFactor = null;

    public ?bool $isActive = null;

    public function fill(array $data): static
    {
        $known = ['id', 'baseUomId', 'purchaseUomId', 'salesUomId', 'inventoryUomId', 'purchaseFactor', 'salesFactor', 'inventoryFactor', 'isActive'];
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
            'baseUomId'      => 'nullable|integer|exists:units_of_measure,id',
            'purchaseUomId'  => 'nullable|integer|exists:units_of_measure,id',
            'salesUomId'     => 'nullable|integer|exists:units_of_measure,id',
            'inventoryUomId' => 'nullable|integer|exists:units_of_measure,id',
            'purchaseFactor' => 'numeric|min:0',
            'salesFactor'    => 'numeric|min:0',
            'inventoryFactor' => 'numeric|min:0',
            'isActive'       => 'boolean',
        ];
    }
}
