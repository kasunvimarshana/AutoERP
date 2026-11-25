<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Constants\OperationalFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Models\VehicleUse;

final class VehicleUseRegisterService
{
    public function __construct(private readonly RentalAuthorization $authorization) {}

    public function list(AgreementContext $context, array $filters, int $perPage): LengthAwarePaginator
    {
        $this->authorization->assertUse($context, false);
        foreach (['from', 'until', 'use_status', 'search', 'kind'] as $key) {
            if (isset($filters[$key]) && is_string($filters[$key]) && trim($filters[$key]) === '') {
                $filters[$key] = null;
            }
        }
        $filters = Validator::make($filters, [
            'search' => ['nullable', 'string', 'max:255'],
            'use_status' => ['nullable', Rule::enum(VehicleUseStatus::class)],
            'from' => ['nullable', 'string'], 'until' => ['nullable', 'string'],
            'kind' => ['nullable', 'string', 'in:customer,owner,internal'],
        ])->validate();
        $from = empty($filters['from']) ? null : OperationalTime::parse($filters['from'], 'from');
        $until = empty($filters['until']) ? null : OperationalTime::parse($filters['until'], 'until');
        if ($from !== null && $until !== null && $until <= $from) {
            throw ValidationException::withMessages(['until' => ['Period end must follow its start.']]);
        }
        $search = trim($filters['search'] ?? '');

        return VehicleUse::query()->forContext($context->tenantId, $context->organizationUnitId)
            ->with(OperationalFields::USE_RELATIONS)
            ->when($filters['use_status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['kind'] ?? null, function ($q, $kind) {
                if ($kind === AgreementKind::Owner->value) {
                    $q->whereNotNull('owner_agreement_id');
                } elseif ($kind === AgreementKind::Customer->value) {
                    $q->whereNotNull('customer_agreement_id');
                } elseif ($kind === 'internal') {
                    $q->whereNull('customer_agreement_id');
                }
            })
            ->when($from !== null, fn ($q) => $q->where(fn ($end) => $end->whereNull('ends_at')->orWhere('ends_at', '>', OperationalTime::database($from))))
            ->when($until !== null, fn ($q) => $q->where('starts_at', '<', OperationalTime::database($until)))
            ->when($search !== '', fn ($q) => $q->where(function ($match) use ($search): void {
                $pattern = '%'.$search.'%';
                $match->where('vehicle_label_snapshot', 'like', $pattern)
                    ->orWhere('notes', 'like', $pattern)
                    ->orWhereHas('vehicle', fn ($v) => $v->where('registration_number', 'like', $pattern)->orWhere('vehicle_number', 'like', $pattern))
                    ->orWhereHas('customerAgreement', fn ($agreement) => $agreement->where('reference', 'like', $pattern)->orWhere('party_name_snapshot', 'like', $pattern))
                    ->orWhereHas('ownerAgreement', fn ($agreement) => $agreement->where('reference', 'like', $pattern)->orWhere('party_name_snapshot', 'like', $pattern));
            }))
            ->orderByDesc('starts_at')->orderByDesc('id')->paginate($perPage);
    }
}
