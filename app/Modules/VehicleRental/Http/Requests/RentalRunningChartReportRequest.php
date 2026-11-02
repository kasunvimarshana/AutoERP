<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class RentalRunningChartReportRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'agreement_id' => ['nullable', 'integer', 'min:1'],
            'allocation_id' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (empty($this->input('agreement_id')) && empty($this->input('allocation_id'))) {
                $validator->errors()->add('agreement_id', 'Either an agreement or an allocation must be selected.');
            }
        });
    }
}
