<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Tax\Models\TaxGroup;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAssignmentSide;
use Modules\VehicleRental\Enums\RentalAssignmentStatus;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalAgreementFormLookupRequest;
use Modules\VehicleRental\Http\Resources\RentalAgreementResource;
use Modules\VehicleRental\Http\Resources\RentalAssignmentResource;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAssignment;
use Modules\VehicleRental\Services\Lookups\RentalAgreementReferenceQuery;
use Modules\VehicleRental\Services\RentalAssignmentService;

final class RentalLookupController
{
    public function agreementForm(
        RentalAgreementFormLookupRequest $request,
        RentalAgreementReferenceQuery $references,
    ): JsonResponse {
        $taxGroups = $references
            ->activeTaxGroups($request->tenantId(), $request->organizationUnitId())
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(static fn (TaxGroup $taxGroup): array => [
                'id' => (int) $taxGroup->getKey(),
                'code' => (string) $taxGroup->code,
                'name' => (string) $taxGroup->name,
            ])
            ->values()
            ->all();

        return response()->json(['data' => ['tax_groups' => $taxGroups]]);
    }

    public function assignmentAgreements(ListRentalRequest $request): AnonymousResourceCollection
    {
        return $this->agreements($request);
    }

    public function calculationAgreements(ListRentalRequest $request): AnonymousResourceCollection
    {
        return $this->agreements($request);
    }

    public function assignmentSources(
        ListRentalRequest $request,
        RentalAssignmentService $service,
    ): AnonymousResourceCollection {
        return $this->assignments(
            $request,
            $service,
            RentalAssignmentSide::OwnerSupply,
            [RentalAssignmentStatus::Planned->value, RentalAssignmentStatus::Active->value],
            true,
        );
    }

    public function runningChartAssignments(
        ListRentalRequest $request,
        RentalAssignmentService $service,
    ): AnonymousResourceCollection {
        return $this->assignments(
            $request,
            $service,
            RentalAssignmentSide::CustomerUse,
            [RentalAssignmentStatus::Active->value],
        );
    }

    private function agreements(ListRentalRequest $request): AnonymousResourceCollection
    {
        $query = RentalAgreement::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->where('status', RentalAgreementStatus::Active->value)
            ->with(['customer', 'supplier', 'currency', 'rateVersions.lines'])
            ->orderByDesc('starts_on')
            ->orderByDesc('id');
        if ($request->filled('kind')) {
            $query->where('kind', $request->validated('kind'));
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->validated('search'));
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('agreement_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn (Builder $party): Builder => $party->where('display_name', 'like', "%{$search}%"))
                    ->orWhereHas('supplier', fn (Builder $party): Builder => $party->where('display_name', 'like', "%{$search}%"));
            });
        }

        return RentalAgreementResource::collection($query->paginate($request->perPage()));
    }

    /** @param list<string> $statuses */
    private function assignments(
        ListRentalRequest $request,
        RentalAssignmentService $service,
        RentalAssignmentSide $side,
        array $statuses,
        bool $requireCompleteCoverage = false,
    ): AnonymousResourceCollection {
        $query = RentalAssignment::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->whereIn('status', $statuses)
            ->where('side', $side->value)
            ->with($service->relations())
            ->orderByDesc('starts_at');
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', (int) $request->validated('vehicle_id'));
        }
        if ($requireCompleteCoverage && $request->filled('date_from')) {
            $startsOn = CarbonImmutable::parse((string) $request->validated('date_from'))->toDateString();
            $query->whereDate('starts_at', '<=', $startsOn);

            if ($request->filled('date_to')) {
                $endsOn = CarbonImmutable::parse((string) $request->validated('date_to'))->toDateString();
                $query->where(function (Builder $scope) use ($endsOn): void {
                    $scope->whereNull('ends_at')
                        ->orWhereDate('ends_at', '>=', $endsOn);
                });
            } else {
                $query->whereNull('ends_at');
            }
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->validated('search'));
            $query->where(function (Builder $scope) use ($search): void {
                $scope->whereHas('agreement', fn (Builder $agreement): Builder => $agreement
                    ->where('agreement_number', 'like', "%{$search}%"))
                    ->orWhereHas('vehicle', fn (Builder $vehicle): Builder => $vehicle
                        ->where('vehicle_number', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%"));
            });
        }

        return RentalAssignmentResource::collection($query->paginate($request->perPage()));
    }
}
