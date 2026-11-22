<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\VehicleService\DTOs\VehicleServiceJobFeedbackData;
use Modules\VehicleService\Enums\VehicleServiceFeedbackType;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobFeedback;
use Modules\VehicleService\Services\Concerns\AssertsVehicleServiceExpectedVersion;

final class VehicleServiceJobFeedbackService
{
    use AssertsVehicleServiceExpectedVersion;

    /**
     * @param  array{feedback_type?: string|null, rating?: int|null, customer_visible_only?: bool|null, search?: string|null}  $filters
     * @return Collection<int, VehicleServiceJobFeedback>
     */
    public function list(VehicleServiceJob $job, array $filters = []): Collection
    {
        $query = VehicleServiceJobFeedback::query()
            ->where('tenant_id', $job->tenant_id)
            ->where('vehicle_service_job_id', $job->id)
            ->with(['user:id,name,email', 'creator:id,name', 'updater:id,name']);

        if (! empty($filters['feedback_type'])) {
            $query->where('feedback_type', $filters['feedback_type']);
        }

        if (! empty($filters['rating'])) {
            $query->where('rating', (int) $filters['rating']);
        }

        if (! empty($filters['customer_visible_only'])) {
            $query->where('is_customer_visible', true);
        }

        if (! empty($filters['search'])) {
            $term = '%'.trim((string) $filters['search']).'%';
            $query->where(function ($subQuery) use ($term): void {
                $subQuery->where('comment', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhere('author_name', 'like', $term);
            });
        }

        return $query->latest('created_at')->get();
    }

    /**
     * @return array{
     *     average_rating: float|null,
     *     total_count: int,
     *     ratings_count: int,
     *     reviews_count: int,
     *     notes_count: int,
     *     customer_feedback_count: int,
     *     complaints_count: int,
     *     rating_breakdown: array<int, int>
     * }
     */
    public function summary(VehicleServiceJob $job): array
    {
        $feedbacks = VehicleServiceJobFeedback::query()
            ->where('tenant_id', $job->tenant_id)
            ->where('vehicle_service_job_id', $job->id)
            ->get(['feedback_type', 'rating']);

        $totalCount = $feedbacks->count();
        $reviewsCount = 0;
        $notesCount = 0;
        $customerFeedbackCount = 0;
        $complaintsCount = 0;

        $ratedCount = 0;
        $ratingSum = 0;
        $ratingBreakdown = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        ];

        foreach ($feedbacks as $item) {
            $type = $item->feedback_type instanceof VehicleServiceFeedbackType
                ? $item->feedback_type->value
                : (string) $item->feedback_type;

            match ($type) {
                VehicleServiceFeedbackType::Review->value => $reviewsCount++,
                VehicleServiceFeedbackType::InternalNote->value => $notesCount++,
                VehicleServiceFeedbackType::CustomerFeedback->value => $customerFeedbackCount++,
                VehicleServiceFeedbackType::Complaint->value => $complaintsCount++,
                default => null,
            };

            if ($item->rating !== null && $item->rating >= 1 && $item->rating <= 5) {
                $ratingVal = (int) $item->rating;
                $ratingBreakdown[$ratingVal] = ($ratingBreakdown[$ratingVal] ?? 0) + 1;
                $ratingSum += $ratingVal;
                $ratedCount++;
            }
        }

        $averageRating = $ratedCount > 0 ? round($ratingSum / $ratedCount, 2) : null;

        return [
            'average_rating' => $averageRating,
            'total_count' => $totalCount,
            'ratings_count' => $ratedCount,
            'reviews_count' => $reviewsCount,
            'notes_count' => $notesCount,
            'customer_feedback_count' => $customerFeedbackCount,
            'complaints_count' => $complaintsCount,
            'rating_breakdown' => $ratingBreakdown,
        ];
    }

    public function create(
        VehicleServiceJob $job,
        VehicleServiceJobFeedbackData $data,
        int $actorId,
    ): VehicleServiceJobFeedback {
        return DB::transaction(function () use ($job, $data, $actorId): VehicleServiceJobFeedback {
            if ($data->expectedJobVersion !== null) {
                $this->assertExpectedVersion($job, $data->expectedJobVersion);
                $this->bumpJobVersion($job);
            }

            /** @var VehicleServiceJobFeedback $feedback */
            $feedback = VehicleServiceJobFeedback::query()->create([
                'tenant_id' => $job->tenant_id,
                'organization_unit_id' => $job->organization_unit_id,
                'vehicle_service_job_id' => $job->id,
                'user_id' => $data->userId ?? $actorId,
                'author_name' => $data->authorName,
                'feedback_type' => $data->feedbackType,
                'rating' => $data->rating,
                'title' => $data->title,
                'comment' => $data->comment,
                'is_customer_visible' => $data->isCustomerVisible,
                'row_version' => 1,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            return $feedback->load(['user:id,name,email', 'creator:id,name', 'updater:id,name']);
        });
    }

    public function update(
        VehicleServiceJob $job,
        VehicleServiceJobFeedback $feedback,
        VehicleServiceJobFeedbackData $data,
        int $actorId,
    ): VehicleServiceJobFeedback {
        return DB::transaction(function () use ($job, $feedback, $data, $actorId): VehicleServiceJobFeedback {
            if ($feedback->vehicle_service_job_id !== $job->id || $feedback->tenant_id !== $job->tenant_id) {
                abort(404, 'Feedback record not found for this service job.');
            }

            if ($data->expectedVersion !== null) {
                $this->assertFeedbackExpectedVersion($feedback, $data->expectedVersion);
            }

            if ($data->expectedJobVersion !== null) {
                $this->assertExpectedVersion($job, $data->expectedJobVersion);
                $this->bumpJobVersion($job);
            }

            $feedback->fill([
                'feedback_type' => $data->feedbackType,
                'rating' => $data->rating,
                'title' => $data->title,
                'comment' => $data->comment,
                'is_customer_visible' => $data->isCustomerVisible,
                'author_name' => $data->authorName,
                'row_version' => (int) $feedback->row_version + 1,
                'updated_by' => $actorId,
            ]);
            $feedback->save();

            return $feedback->load(['user:id,name,email', 'creator:id,name', 'updater:id,name']);
        });
    }

    public function delete(
        VehicleServiceJob $job,
        VehicleServiceJobFeedback $feedback,
        int $actorId,
        ?int $expectedVersion = null,
    ): void {
        DB::transaction(function () use ($job, $feedback, $expectedVersion): void {
            if ($feedback->vehicle_service_job_id !== $job->id || $feedback->tenant_id !== $job->tenant_id) {
                abort(404, 'Feedback record not found for this service job.');
            }

            if ($expectedVersion !== null) {
                $this->assertFeedbackExpectedVersion($feedback, $expectedVersion);
            }

            $feedback->delete();
        });
    }

    private function assertFeedbackExpectedVersion(VehicleServiceJobFeedback $feedback, int $expectedVersion): void
    {
        if ((int) $feedback->row_version === $expectedVersion) {
            return;
        }

        throw ValidationException::withMessages([
            'expected_version' => ['Feedback was modified by another user. Reload it before continuing.'],
        ]);
    }
}
