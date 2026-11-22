<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\VehicleService\Enums\VehicleServiceFeedbackType;

final class VehicleServiceJobFeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = $this->feedback_type instanceof VehicleServiceFeedbackType
            ? $this->feedback_type
            : VehicleServiceFeedbackType::tryFrom((string) $this->feedback_type);

        $authorDisplay = $this->author_name ?: ($this->user?->name ?? 'Staff');

        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'tenant_id' => (int) $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id === null ? null : (int) $this->organization_unit_id,
            'vehicle_service_job_id' => (int) $this->vehicle_service_job_id,
            'user_id' => $this->user_id === null ? null : (int) $this->user_id,
            'author_name' => (string) $authorDisplay,
            'user' => $this->user ? [
                'id' => (int) $this->user->id,
                'name' => (string) $this->user->name,
                'email' => (string) $this->user->email,
            ] : null,
            'feedback_type' => $type?->value ?? (string) $this->feedback_type,
            'feedback_type_label' => $type?->label() ?? ucfirst((string) $this->feedback_type),
            'rating' => $this->rating === null ? null : (int) $this->rating,
            'title' => $this->title === null ? null : (string) $this->title,
            'comment' => $this->comment === null ? null : (string) $this->comment,
            'is_customer_visible' => (bool) $this->is_customer_visible,
            'created_by' => $this->created_by === null ? null : (int) $this->created_by,
            'created_by_name' => $this->creator?->name,
            'updated_by' => $this->updated_by === null ? null : (int) $this->updated_by,
            'updated_by_name' => $this->updater?->name,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
