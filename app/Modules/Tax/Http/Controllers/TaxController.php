<?php

declare(strict_types=1);

namespace Modules\Tax\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Finance\Models\FinanceAccount;
use Modules\Tax\DTOs\TaxAmountData;
use Modules\Tax\DTOs\TaxCalculationResult;
use Modules\Tax\DTOs\TaxLineCalculationResult;
use Modules\Tax\Http\Requests\ListTaxRequest;
use Modules\Tax\Http\Requests\TaxCalculationRequest;
use Modules\Tax\Http\Requests\UpsertCustomerTaxProfileRequest;
use Modules\Tax\Http\Requests\UpsertSupplierTaxProfileRequest;
use Modules\Tax\Http\Requests\UpsertTaxGroupRequest;
use Modules\Tax\Http\Requests\UpsertTaxPostingProfileRequest;
use Modules\Tax\Http\Requests\UpsertTaxRateRequest;
use Modules\Tax\Http\Requests\UpsertTaxRequest;
use Modules\Tax\Http\Resources\TaxGroupResource;
use Modules\Tax\Http\Resources\TaxPostingProfileResource;
use Modules\Tax\Http\Resources\TaxProfileResource;
use Modules\Tax\Http\Resources\TaxResource;
use Modules\Tax\Http\Resources\TaxTransactionResource;
use Modules\Tax\Models\CustomerTaxProfile;
use Modules\Tax\Models\SupplierTaxProfile;
use Modules\Tax\Models\Tax;
use Modules\Tax\Models\TaxGroup;
use Modules\Tax\Models\TaxPostingProfile;
use Modules\Tax\Services\TaxCalculationService;
use Modules\Tax\Services\TaxMasterDataService;
use Modules\Tax\Services\TaxReportService;

final class TaxController
{
    public function taxes(ListTaxRequest $request): AnonymousResourceCollection
    {
        $query = $this->scope(Tax::query(), $request)->with('rates');
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn (Builder $scope): Builder => $scope
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('tax_type', 'like', "%{$search}%"));
        }
        if ($request->filled('tax_type')) {
            $query->where('tax_type', $request->input('tax_type'));
        }
        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        return TaxResource::collection($query->orderBy('code')->paginate($request->perPage()));
    }

    public function storeTax(UpsertTaxRequest $request, TaxMasterDataService $service): TaxResource
    {
        return new TaxResource($service->saveTax($request->validated()));
    }

    public function showTax(ListTaxRequest $request, int $tax): TaxResource
    {
        return new TaxResource($this->scope(Tax::query(), $request)->with('rates')->findOrFail($tax));
    }

    public function updateTax(UpsertTaxRequest $request, int $tax, TaxMasterDataService $service): TaxResource
    {
        $model = $this->scope(Tax::query(), $request)->findOrFail($tax);

        return new TaxResource($service->saveTax($request->validated(), $model));
    }

    public function addRate(
        UpsertTaxRateRequest $request,
        int $tax,
        TaxMasterDataService $service,
    ): JsonResponse {
        $model = $this->scope(Tax::query(), $request)->findOrFail($tax);

        return response()->json(['data' => $service->saveRate($model, $request->validated())]);
    }

    public function groups(ListTaxRequest $request): AnonymousResourceCollection
    {
        return TaxGroupResource::collection(
            $this->scope(TaxGroup::query(), $request)
                ->with('lines.tax')
                ->orderBy('code')
                ->paginate($request->perPage()),
        );
    }

    public function storeGroup(UpsertTaxGroupRequest $request, TaxMasterDataService $service): TaxGroupResource
    {
        return new TaxGroupResource($service->saveGroup(
            $request->validated(),
            $request->input('lines', []),
        ));
    }

    public function updateGroup(
        UpsertTaxGroupRequest $request,
        int $group,
        TaxMasterDataService $service,
    ): TaxGroupResource {
        $model = $this->scope(TaxGroup::query(), $request)->findOrFail($group);

        return new TaxGroupResource($service->saveGroup(
            $request->validated(),
            $request->input('lines', []),
            $model,
        ));
    }

    public function customerProfiles(ListTaxRequest $request): AnonymousResourceCollection
    {
        return TaxProfileResource::collection(
            $this->scope(CustomerTaxProfile::query(), $request)
                ->with(['customer', 'taxGroup'])
                ->orderByDesc('id')
                ->paginate($request->perPage()),
        );
    }

    public function storeCustomerProfile(
        UpsertCustomerTaxProfileRequest $request,
        TaxMasterDataService $service,
    ): TaxProfileResource {
        return new TaxProfileResource($service->saveCustomerProfile($request->validated()));
    }

    public function updateCustomerProfile(
        UpsertCustomerTaxProfileRequest $request,
        int $profile,
        TaxMasterDataService $service,
    ): TaxProfileResource {
        $model = $this->scope(CustomerTaxProfile::query(), $request)->findOrFail($profile);

        return new TaxProfileResource($service->saveCustomerProfile($request->validated(), $model));
    }

    public function supplierProfiles(ListTaxRequest $request): AnonymousResourceCollection
    {
        return TaxProfileResource::collection(
            $this->scope(SupplierTaxProfile::query(), $request)
                ->with(['supplier', 'taxGroup'])
                ->orderByDesc('id')
                ->paginate($request->perPage()),
        );
    }

    public function storeSupplierProfile(
        UpsertSupplierTaxProfileRequest $request,
        TaxMasterDataService $service,
    ): TaxProfileResource {
        return new TaxProfileResource($service->saveSupplierProfile($request->validated()));
    }

    public function updateSupplierProfile(
        UpsertSupplierTaxProfileRequest $request,
        int $profile,
        TaxMasterDataService $service,
    ): TaxProfileResource {
        $model = $this->scope(SupplierTaxProfile::query(), $request)->findOrFail($profile);

        return new TaxProfileResource($service->saveSupplierProfile($request->validated(), $model));
    }

    public function postingProfiles(ListTaxRequest $request): AnonymousResourceCollection
    {
        return TaxPostingProfileResource::collection(
            $this->scope(TaxPostingProfile::query(), $request)
                ->with(['tax', 'account'])
                ->orderByDesc('id')
                ->paginate($request->perPage()),
        );
    }

    public function storePostingProfile(
        UpsertTaxPostingProfileRequest $request,
        TaxMasterDataService $service,
    ): TaxPostingProfileResource {
        return new TaxPostingProfileResource($service->savePostingProfile($request->validated()));
    }

    public function updatePostingProfile(
        UpsertTaxPostingProfileRequest $request,
        int $profile,
        TaxMasterDataService $service,
    ): TaxPostingProfileResource {
        $model = $this->scope(TaxPostingProfile::query(), $request)->findOrFail($profile);

        return new TaxPostingProfileResource($service->savePostingProfile($request->validated(), $model));
    }

    public function calculate(TaxCalculationRequest $request, TaxCalculationService $service): JsonResponse
    {
        return response()->json(['data' => $this->calculationToArray($service->calculate($request->toData()))]);
    }

    public function report(ListTaxRequest $request, string $report, TaxReportService $service): JsonResponse|AnonymousResourceCollection
    {
        if ($report === 'transactions') {
            return TaxTransactionResource::collection(
                $service->transactions($request->tenantId(), $request->organizationUnitId(), $request->validated(), $request->perPage()),
            );
        }

        return response()->json([
            'data' => $service->report($report, $request->tenantId(), $request->organizationUnitId(), $request->validated()),
        ]);
    }

    public function lookups(ListTaxRequest $request): JsonResponse
    {
        $taxes = $this->scope(Tax::query(), $request)
            ->where('active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'tax_type', 'calculation_method']);
        $groups = $this->scope(TaxGroup::query(), $request)
            ->where('active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'is_default']);
        $accounts = $this->scope(FinanceAccount::query(), $request)
            ->where('is_active', true)
            ->where('is_posting_account', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'is_tax_account']);

        return response()->json([
            'data' => [
                'taxes' => $taxes,
                'groups' => $groups,
                'accounts' => $accounts,
                'calculation_methods' => config('tax.calculation_methods', []),
                'exemption_statuses' => config('tax.exemption_statuses', []),
                'posting_directions' => config('tax.posting_directions', []),
            ],
        ]);
    }

    private function scope(Builder $query, ListTaxRequest|UpsertTaxRequest|UpsertTaxRateRequest|UpsertTaxGroupRequest|UpsertCustomerTaxProfileRequest|UpsertSupplierTaxProfileRequest|UpsertTaxPostingProfileRequest $request): Builder
    {
        $query->where('tenant_id', $request->tenantId());

        return $request->organizationUnitId() === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $request->organizationUnitId());
    }

    /**
     * @return array<string, mixed>
     */
    private function calculationToArray(TaxCalculationResult $result): array
    {
        return [
            'taxable_amount' => $result->taxableAmount,
            'tax_amount' => $result->taxAmount,
            'withholding_amount' => $result->withholdingAmount,
            'total_amount' => $result->totalAmount,
            'line_tax_amount' => $result->lineTaxAmount,
            'header_tax_amount' => $result->headerTaxAmount,
            'line_results' => array_map(fn (TaxLineCalculationResult $line): array => [
                'line_number' => $line->lineNumber,
                'taxable_amount' => $line->taxableAmount,
                'tax_amount' => $line->taxAmount,
                'withholding_amount' => $line->withholdingAmount,
                'total_amount' => $line->totalAmount,
                'taxes' => array_map(fn (TaxAmountData $tax): array => $this->taxAmountToArray($tax), $line->taxes),
            ], $result->lineResults),
            'header_taxes' => array_map(fn (TaxAmountData $tax): array => $this->taxAmountToArray($tax), $result->headerTaxes),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function taxAmountToArray(TaxAmountData $tax): array
    {
        return [
            'tax_id' => $tax->taxId,
            'tax_code' => $tax->taxCode,
            'tax_name' => $tax->taxName,
            'tax_type' => $tax->taxType,
            'calculation_method' => $tax->calculationMethod,
            'rate' => $tax->rate,
            'sequence' => $tax->sequence,
            'taxable_amount' => $tax->taxableAmount,
            'tax_amount' => $tax->taxAmount,
            'total_after_tax' => $tax->totalAfterTax,
            'is_withholding' => $tax->isWithholding,
            'recoverable' => $tax->recoverable,
            'payable' => $tax->payable,
            'receivable' => $tax->receivable,
        ];
    }
}
