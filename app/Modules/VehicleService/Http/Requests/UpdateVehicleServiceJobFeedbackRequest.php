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

final class UpdateVehicleServiceJobFeedbackRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => ['nullable', 'integer', 'min:1'],
            'expected_job_version' => ['nullable', 'integer', 'min:1'],
            'feedback_type' => ['nullable', 'string', Rule::enum(VehicleServiceFeedbackType::class)],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:5000'],
            'is_customer_visible' => ['nullable', 'boolean'],
            'author_name' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function toData(?int $currentUserId = null): VehicleServiceJobFeedbackData
    {
        $rawType = $this->input('feedback_type');
        $type = $rawType !== null && $rawType !== ''
            ? VehicleServiceFeedbackType::tryFrom((string) $rawType)
            : null;

        $isCustomerVisible = $this->has('is_customer_visible')
            ? (bool) $this->boolean('is_customer_visible')
            : null;

        $authorName = $this->has('author_name')
            ? ($this->filled('author_name') ? trim((string) $this->input('author_name')) : null)
            : null;

        $title = $this->has('title')
            ? ($this->filled('title') ? trim((string) $this->input('title')) : null)
            : null;

        $comment = $this->has('comment')
            ? ($this->filled('comment') ? trim((string) $this->input('comment')) : null)
            : null;

        $rating = $this->has('rating')
            ? ($this->filled('rating') ? (int) $this->input('rating') : null)
            : null;

        return new VehicleServiceJobFeedbackData(
            feedbackType: $type,
            comment: $comment,
            rating: $rating,
            title: $title,
            isCustomerVisible: $isCustomerVisible,
            authorName: $authorName,
            userId: $currentUserId,
            expectedVersion: $this->filled('expected_version') ? (int) $this->input('expected_version') : null,
            expectedJobVersion: $this->filled('expected_job_version') ? (int) $this->input('expected_job_version') : null,
            hasFeedbackType: $this->has('feedback_type'),
            hasComment: $this->has('comment'),
            hasRating: $this->has('rating'),
            hasTitle: $this->has('title'),
            hasIsCustomerVisible: $this->has('is_customer_visible'),
            hasAuthorName: $this->has('author_name'),
        );
    }
}
