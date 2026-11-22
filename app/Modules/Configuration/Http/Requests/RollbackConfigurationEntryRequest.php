<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Configuration\Http\Requests;

final class RollbackConfigurationEntryRequest extends ManageConfigurationEntryRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'revision_id' => ['required', 'integer', 'min:1'],
            'expected_version' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
