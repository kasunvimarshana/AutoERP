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
            ->with(['user:id,first_name,last_name,email,username', 'creator:id,first_name,last_name', 'updater:id,first_name,last_name']);

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
            $rawSearch = trim((string) $filters['search']);
            $term = '%'.$rawSearch.'%';
            $query->where(function ($subQuery) use ($term, $rawSearch): void {
                $subQuery->where('comment', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhere('author_name', 'like', $term)
                    ->orWhereHas('user', function ($userQuery) use ($term, $rawSearch): void {
                        $userQuery->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('username', 'like', $term)
                            ->orWhere('email', 'like', $term);

                        $words = preg_split('/\s+/', $rawSearch, -1, PREG_SPLIT_NO_EMPTY);
                        if ($words && count($words) > 1) {
                            $userQuery->orWhere(function ($multiQuery) use ($words): void {
                                $multiQuery->where('first_name', 'like', '%'.$words[0].'%')
                                    ->where('last_name', 'like', '%'.$words[count($words) - 1].'%');
                            });
                        }
                    });
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
                /** @var VehicleServiceJob $lockedJob */
                $lockedJob = VehicleServiceJob::query()
                    ->where('tenant_id', $job->tenant_id)
                    ->lockForUpdate()
                    ->findOrFail($job->getKey());
                $this->assertExpectedVersion($lockedJob, $data->expectedJobVersion);
                $this->bumpJobVersion($lockedJob);
            }

            $feedbackType = $data->feedbackType ?? VehicleServiceFeedbackType::Review;
            $isCustomerVisible = $data->isCustomerVisible ?? ($feedbackType !== VehicleServiceFeedbackType::InternalNote);

            /** @var VehicleServiceJobFeedback $feedback */
            $feedback = VehicleServiceJobFeedback::query()->create([
                'tenant_id' => $job->tenant_id,
                'organization_unit_id' => $job->organization_unit_id,
                'vehicle_service_job_id' => $job->id,
                'user_id' => $data->userId ?? $actorId,
                'author_name' => $data->authorName,
                'feedback_type' => $feedbackType,
                'rating' => $data->rating,
                'title' => $data->title,
                'comment' => $data->comment,
                'is_customer_visible' => $isCustomerVisible,
                'row_version' => 1,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            return $feedback->load([
                'user:id,first_name,last_name,email,username',
                'creator:id,first_name,last_name',
                'updater:id,first_name,last_name',
            ]);
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

            /** @var VehicleServiceJobFeedback $lockedFeedback */
            $lockedFeedback = VehicleServiceJobFeedback::query()
                ->where('tenant_id', $job->tenant_id)
                ->where('vehicle_service_job_id', $job->id)
                ->lockForUpdate()
                ->findOrFail($feedback->getKey());

            if ($data->expectedVersion !== null) {
                $this->assertFeedbackExpectedVersion($lockedFeedback, $data->expectedVersion);
            }

            if ($data->expectedJobVersion !== null) {
                /** @var VehicleServiceJob $lockedJob */
                $lockedJob = VehicleServiceJob::query()
                    ->where('tenant_id', $job->tenant_id)
                    ->lockForUpdate()
                    ->findOrFail($job->getKey());
                $this->assertExpectedVersion($lockedJob, $data->expectedJobVersion);
                $this->bumpJobVersion($lockedJob);
            }

            $fillable = [
                'row_version' => (int) $lockedFeedback->row_version + 1,
                'updated_by' => $actorId,
            ];

            if ($data->hasFeedbackType && $data->feedbackType !== null) {
                $fillable['feedback_type'] = $data->feedbackType;
            }
            if ($data->hasRating) {
                $fillable['rating'] = $data->rating;
            }
            if ($data->hasTitle) {
                $fillable['title'] = $data->title;
            }
            if ($data->hasComment) {
                $fillable['comment'] = $data->comment;
            }
            if ($data->hasIsCustomerVisible && $data->isCustomerVisible !== null) {
                $fillable['is_customer_visible'] = $data->isCustomerVisible;
            }
            if ($data->hasAuthorName) {
                $fillable['author_name'] = $data->authorName;
            }

            $lockedFeedback->fill($fillable)->save();

            return $lockedFeedback->load([
                'user:id,first_name,last_name,email,username',
                'creator:id,first_name,last_name',
                'updater:id,first_name,last_name',
            ]);
        });
    }

    public function delete(
        VehicleServiceJob $job,
        VehicleServiceJobFeedback $feedback,
        int $actorId,
        ?int $expectedVersion = null,
        ?int $expectedJobVersion = null,
    ): void {
        DB::transaction(function () use ($job, $feedback, $expectedVersion, $expectedJobVersion): void {
            if ($feedback->vehicle_service_job_id !== $job->id || $feedback->tenant_id !== $job->tenant_id) {
                abort(404, 'Feedback record not found for this service job.');
            }

            /** @var VehicleServiceJobFeedback $lockedFeedback */
            $lockedFeedback = VehicleServiceJobFeedback::query()
                ->where('tenant_id', $job->tenant_id)
                ->where('vehicle_service_job_id', $job->id)
                ->lockForUpdate()
                ->findOrFail($feedback->getKey());

            if ($expectedVersion !== null) {
                $this->assertFeedbackExpectedVersion($lockedFeedback, $expectedVersion);
            }

            if ($expectedJobVersion !== null) {
                /** @var VehicleServiceJob $lockedJob */
                $lockedJob = VehicleServiceJob::query()
                    ->where('tenant_id', $job->tenant_id)
                    ->lockForUpdate()
                    ->findOrFail($job->getKey());
                $this->assertExpectedVersion($lockedJob, $expectedJobVersion);
                $this->bumpJobVersion($lockedJob);
            }

            $lockedFeedback->delete();
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
