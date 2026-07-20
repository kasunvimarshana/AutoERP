<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Tax\Models\TaxGroup;
use Modules\VehicleRental\Http\Requests\RentalAgreementFormLookupRequest;
use Modules\VehicleRental\Services\Lookups\RentalAgreementReferenceQuery;

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
}
