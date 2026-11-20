<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Models\RunningChart;

final class RunningChartRegisterService
{
    public function __construct(private readonly RentalAuthorization $authorization) {}

    public function list(AgreementContext $context, array $filters, int $perPage): LengthAwarePaginator
    {
        $this->authorization->assertChart($context, RunningChartAction::Create, false);
        foreach (['from', 'until', 'chart_status', 'search', 'customer_agreement_id', 'owner_agreement_id', 'vehicle_use_id', 'kind'] as $key) {
            if (isset($filters[$key]) && is_string($filters[$key]) && trim($filters[$key]) === '') {
                $filters[$key] = null;
            }
        }
        $filters = Validator::make($filters, [
            'search' => ['nullable', 'string', 'max:255'],
            'chart_status' => ['nullable', Rule::enum(RunningChartStatus::class)],
            'from' => ['nullable', 'string'], 'until' => ['nullable', 'string'],
            'customer_agreement_id' => ['nullable', 'integer', 'min:1'],
            'owner_agreement_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_use_id' => ['nullable', 'integer', 'min:1'],
            'kind' => ['nullable', 'string', Rule::enum(AgreementKind::class)],
        ])->validate();
        $from = empty($filters['from']) ? null : OperationalTime::parse($filters['from'], 'from');
        $until = empty($filters['until']) ? null : OperationalTime::parse($filters['until'], 'until');
        if ($from !== null && $until !== null && $until <= $from) {
            throw ValidationException::withMessages(['until' => ['Period end must follow its start.']]);
        }
        $search = trim($filters['search'] ?? '');

        return RunningChart::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['vehicleUse.customerAgreement', 'vehicleUse.ownerAgreement', 'vehicleUse.replacesUse', 'vehicleUse.replacedByUse', 'vehicleUse.vehicle', 'correctsChart', 'correctedByChart'])
            ->when($filters['chart_status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($from !== null, fn ($q) => $q->where('ends_at', '>', OperationalTime::database($from)))
            ->when($until !== null, fn ($q) => $q->where('starts_at', '<', OperationalTime::database($until)))
            ->when($filters['vehicle_use_id'] ?? null, fn ($q, $useId) => $q->where('vehicle_use_id', (int) $useId))
            ->when($filters['customer_agreement_id'] ?? null, fn ($q, $custAgId) => $q->whereHas('vehicleUse', fn ($u) => $u->where('customer_agreement_id', (int) $custAgId)))
            ->when($filters['owner_agreement_id'] ?? null, fn ($q, $ownerAgId) => $q->whereHas('vehicleUse', fn ($u) => $u->where('owner_agreement_id', (int) $ownerAgId)))
            ->when($filters['kind'] ?? null, function ($q, $kind) {
                if ($kind === AgreementKind::Owner->value) {
                    $q->whereHas('vehicleUse', fn ($u) => $u->whereNotNull('owner_agreement_id'));
                } elseif ($kind === AgreementKind::Customer->value) {
                    $q->whereHas('vehicleUse', fn ($u) => $u->whereNull('owner_agreement_id'));
                }
            })
            ->when($search !== '', fn ($q) => $q->where(function ($match) use ($search): void {
                $pattern = '%'.$search.'%';
                $match->where('reference', 'like', $pattern)
                    ->orWhere('driver_name_snapshot', 'like', $pattern)
                    ->orWhere('driver_reference_snapshot', 'like', $pattern)
                    ->orWhere('notes', 'like', $pattern)
                    ->orWhere('driver_observation', 'like', $pattern)
                    ->orWhereHas('vehicleUse', function ($use) use ($pattern): void {
                        $use->where('vehicle_label_snapshot', 'like', $pattern)
                            ->orWhereHas('vehicle', fn ($v) => $v->where('registration_number', 'like', $pattern)->orWhere('vehicle_number', 'like', $pattern))
                            ->orWhereHas('customerAgreement', fn ($agreement) => $agreement->where('reference', 'like', $pattern)->orWhere('party_name_snapshot', 'like', $pattern))
                            ->orWhereHas('ownerAgreement', fn ($agreement) => $agreement->where('reference', 'like', $pattern)->orWhere('party_name_snapshot', 'like', $pattern));
                    });
            }))
            ->orderByDesc('starts_at')->orderByDesc('id')->paginate($perPage);
    }
}
