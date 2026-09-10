<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Constants\OperationalFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Models\VehicleUse;

final class VehicleUseRegisterService
{
    public function __construct(private readonly RentalAuthorization $authorization) {}

    public function list(AgreementContext $context, array $filters, int $perPage): LengthAwarePaginator
    {
        $this->authorization->assertUse($context, false);
        $filters = Validator::make($filters, [
            'search' => ['nullable', 'string', 'max:'.AgreementFields::REFERENCE_LENGTH],
            'use_status' => ['nullable', Rule::enum(VehicleUseStatus::class)],
            'from' => ['nullable', 'string'], 'until' => ['nullable', 'string'],
        ])->validate();
        $from = ($filters['from'] ?? null) === null ? null : OperationalTime::parse($filters['from'], 'from');
        $until = ($filters['until'] ?? null) === null ? null : OperationalTime::parse($filters['until'], 'until');
        if ($from !== null && $until !== null && $until <= $from) {
            throw ValidationException::withMessages(['until' => ['Period end must follow its start.']]);
        }
        $search = trim($filters['search'] ?? '');

        return VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->with(OperationalFields::USE_RELATIONS)
            ->when($filters['use_status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($from !== null, fn ($q) => $q->where(fn ($end) => $end->whereNull('ends_at')->orWhere('ends_at', '>', OperationalTime::database($from))))
            ->when($until !== null, fn ($q) => $q->where('starts_at', '<', OperationalTime::database($until)))
            ->when($search !== '', fn ($q) => $q->where(function ($match) use ($search): void {
                $pattern = '%'.$search.'%';
                $match->where('vehicle_label_snapshot', 'like', $pattern)
                    ->orWhereHas('customerAgreement', fn ($agreement) => $agreement->where('reference', 'like', $pattern)->orWhere('party_name_snapshot', 'like', $pattern))
                    ->orWhereHas('ownerAgreement', fn ($agreement) => $agreement->where('reference', 'like', $pattern)->orWhere('party_name_snapshot', 'like', $pattern));
            }))
            ->orderByDesc('starts_at')->orderByDesc('id')->paginate($perPage);
    }
}
