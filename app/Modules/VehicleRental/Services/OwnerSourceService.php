<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\OwnerAgreement;

final class OwnerSourceService
{
    public function __construct(
        private readonly RentalAuthorization $authorization,
        private readonly RentalCalendar $calendar,
    ) {}

    public function list(AgreementContext $context, int $vehicle, array $input, int $perPage): LengthAwarePaginator
    {
        $this->authorization->assertUse($context, false);
        foreach (['ends_at', 'customer_agreement_id', 'search'] as $key) {
            if (array_key_exists($key, $input) && is_string($input[$key]) && trim($input[$key]) === '') {
                $input[$key] = null;
            }
        }
        $data = Validator::make($input, [
            'starts_at' => ['required', 'string'],
            'ends_at' => ['sometimes', 'nullable', 'string'],
            'customer_agreement_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:255'],
        ])->validate();
        $endsAt = $data['ends_at'] ?? null;
        if (is_string($endsAt) && trim($endsAt) === '') {
            $endsAt = null;
        }
        [$start, $end] = OperationalTime::plannedPeriod($data['starts_at'], $endsAt);
        $coverageFrom = $this->calendar->civilDate($context, $start);

        $coverageUntil = null;
        if ($end !== null) {
            $coverageUntil = $this->calendar->civilDate($context, $end->subMicrosecond());
        } elseif (! empty($data['customer_agreement_id'])) {
            $customerAgreement = CustomerAgreement::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->find((int) $data['customer_agreement_id']);
            if ($customerAgreement?->ends_on !== null) {
                $coverageUntil = $customerAgreement->ends_on->toDateString();
            }
        }

        $search = trim($data['search'] ?? '');

        return OwnerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->where('vehicle_id', $vehicle)->where('status', AgreementStatus::Active->value)
            ->where('starts_on', '<=', $coverageFrom)
            ->where(function ($coverage) use ($coverageUntil): void {
                $coverage->whereNull('ends_on');
                if ($coverageUntil !== null) {
                    $coverage->orWhere('ends_on', '>=', $coverageUntil);
                }
            })
            ->when($search !== '', fn ($q) => $q->where(fn ($terms) => $terms->where('reference', 'like', '%'.$search.'%')->orWhere('party_name_snapshot', 'like', '%'.$search.'%')))
            ->orderBy('reference')->orderBy('id')->paginate($perPage);
    }
}
