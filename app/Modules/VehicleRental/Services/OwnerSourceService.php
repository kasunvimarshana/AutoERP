<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Models\OwnerAgreement;

final class OwnerSourceService
{
    public function __construct(private readonly RentalAuthorization $authorization) {}

    public function list(AgreementContext $context, int $vehicle, array $input, int $perPage): LengthAwarePaginator
    {
        $this->authorization->assertUse($context, false);
        $data = Validator::make($input, [
            'starts_at' => ['required', 'string'], 'ends_at' => ['present', 'nullable', 'string'],
            'search' => ['nullable', 'string', 'max:'.AgreementFields::REFERENCE_LENGTH],
        ])->validate();
        [$start, $end] = OperationalTime::plannedPeriod($data['starts_at'], $data['ends_at']);
        $search = trim($data['search'] ?? '');

        return OwnerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->where('vehicle_id', $vehicle)->where('status', AgreementStatus::Active->value)
            ->where('starts_on', '<=', $start->toDateString())
            ->where(function ($coverage) use ($end): void {
                $coverage->whereNull('ends_on');
                if ($end !== null) {
                    $coverage->orWhere('ends_on', '>=', $end->subSecond()->toDateString());
                }
            })
            ->when($search !== '', fn ($q) => $q->where(fn ($terms) => $terms->where('reference', 'like', '%'.$search.'%')->orWhere('party_name_snapshot', 'like', '%'.$search.'%')))
            ->orderBy('reference')->orderBy('id')->paginate($perPage);
    }
}
