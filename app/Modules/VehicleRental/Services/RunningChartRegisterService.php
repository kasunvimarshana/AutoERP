<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Models\RunningChart;

final class RunningChartRegisterService
{
    public function __construct(private readonly RentalAuthorization $authorization) {}

    public function list(AgreementContext $context, array $filters, int $perPage): LengthAwarePaginator
    {
        $this->authorization->assertChart($context, RunningChartAction::Create, false);
        $filters = Validator::make($filters, [
            'search' => ['nullable', 'string', 'max:'.AgreementFields::REFERENCE_LENGTH],
            'chart_status' => ['nullable', Rule::enum(RunningChartStatus::class)],
            'from' => ['nullable', 'string'], 'until' => ['nullable', 'string'],
        ])->validate();
        $from = ($filters['from'] ?? null) === null ? null : OperationalTime::parse($filters['from'], 'from');
        $until = ($filters['until'] ?? null) === null ? null : OperationalTime::parse($filters['until'], 'until');
        if ($from !== null && $until !== null && $until <= $from) {
            throw ValidationException::withMessages(['until' => ['Period end must follow its start.']]);
        }
        $search = trim($filters['search'] ?? '');

        return RunningChart::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['vehicleUse.customerAgreement', 'vehicleUse.ownerAgreement', 'vehicleUse.replacesUse', 'correctsChart'])
            ->when($filters['chart_status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($from !== null, fn ($q) => $q->where('ends_at', '>', OperationalTime::database($from)))
            ->when($until !== null, fn ($q) => $q->where('starts_at', '<', OperationalTime::database($until)))
            ->when($search !== '', fn ($q) => $q->where(function ($match) use ($search): void {
                $pattern = '%'.$search.'%';
                $match->where('reference', 'like', $pattern)->orWhereHas('vehicleUse', function ($use) use ($pattern): void {
                    $use->where('vehicle_label_snapshot', 'like', $pattern)
                        ->orWhereHas('customerAgreement', fn ($agreement) => $agreement->where('reference', 'like', $pattern)->orWhere('party_name_snapshot', 'like', $pattern))
                        ->orWhereHas('ownerAgreement', fn ($agreement) => $agreement->where('reference', 'like', $pattern)->orWhere('party_name_snapshot', 'like', $pattern));
                });
            }))
            ->orderByDesc('starts_at')->orderByDesc('id')->paginate($perPage);
    }
}
