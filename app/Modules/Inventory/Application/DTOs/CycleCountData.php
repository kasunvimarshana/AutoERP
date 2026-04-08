<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class CycleCountData extends BaseDto
{
    public string $warehouse_id = '';

    public ?string $location_id = null;

    public string $count_number = '';

    public string $counted_at = '';

    public int $counted_by = 0;

    public ?string $notes = null;

    /** @var array<int, array<string, mixed>> */
    public array $lines = [];
}
