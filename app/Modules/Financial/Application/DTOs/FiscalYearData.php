<?php

declare(strict_types=1);

namespace Modules\Financial\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class FiscalYearData extends BaseDto
{
    public ?string $id = null;
    public string $name = '';
    public string $startDate = '';
    public string $endDate = '';
    public string $status = 'open';

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
            'status'     => ['sometimes', 'string', 'in:open,closed'],
        ];
    }
}
