<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServiceJobFeedbackData;
use Modules\VehicleService\Enums\VehicleServiceFeedbackType;

final class StoreVehicleServiceJobFeedbackRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'feedback_type' => ['required', 'string', Rule::enum(VehicleServiceFeedbackType::class)],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['required', 'string', 'max:5000'],
            'is_customer_visible' => ['nullable', 'boolean'],
            'author_name' => ['nullable', 'string', 'max:150'],
            'expected_version' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toData(?int $currentUserId = null): VehicleServiceJobFeedbackData
    {
        $type = VehicleServiceFeedbackType::from((string) $this->input('feedback_type'));

        return new VehicleServiceJobFeedbackData(
            feedbackType: $type,
            comment: trim((string) $this->input('comment')),
            rating: $this->filled('rating') ? (int) $this->input('rating') : null,
            title: $this->filled('title') ? trim((string) $this->input('title')) : null,
            isCustomerVisible: (bool) $this->boolean('is_customer_visible', $type !== VehicleServiceFeedbackType::InternalNote),
            authorName: $this->filled('author_name') ? trim((string) $this->input('author_name')) : null,
            userId: $currentUserId,
            expectedJobVersion: $this->filled('expected_version') ? (int) $this->input('expected_version') : null,
        );
    }
}
