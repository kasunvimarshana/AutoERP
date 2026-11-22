<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Reporting\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class ServiceJobFeedbackReportRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'date_preset' => ['nullable', 'string', 'in:today,this_week,this_month,this_year,all,custom'],
            'date_by' => ['nullable', 'string', 'in:job_date,feedback_date'],
            'feedback_type' => ['nullable', 'string', 'in:review,internal_note,customer_feedback,complaint,all'],
            'rating' => ['nullable', 'string'],
            'is_customer_visible' => ['nullable', 'string'],
            'job_status' => ['nullable', 'string'],
            'job_type_id' => ['nullable', 'integer', 'min:1'],
            'job_type_code' => ['nullable', 'string', 'max:64'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_id' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
