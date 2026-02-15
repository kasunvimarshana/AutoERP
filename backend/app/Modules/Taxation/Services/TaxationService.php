<?php

declare(strict_types=1);

namespace App\Modules\Taxation\Services;

use App\Core\Services\BaseService;
use App\Core\Exceptions\ServiceException;
use App\Core\DTOs\Taxation\TaxCalculationRequestDTO;
use App\Core\DTOs\Taxation\TaxCalculationResultDTO;
use App\Modules\Taxation\Repositories\TaxGroupRepository;
use App\Modules\Taxation\Repositories\TaxExemptionRepository;
use App\Modules\Taxation\Repositories\TaxJurisdictionRepository;
use App\Modules\Taxation\Repositories\TaxCalculationRepository;
use App\Modules\MasterData\Models\TaxRate;
use App\Modules\Taxation\Models\TaxGroup;
use App\Modules\Taxation\Models\TaxJurisdiction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaxationService extends BaseService
{
    private const ROUNDING_PRECISION = 4;
    private const DISPLAY_PRECISION = 2;

    public function __construct(
        TaxCalculationRepository $repository,
        private readonly TaxGroupRepository $taxGroupRepository,
        private readonly TaxExemptionRepository $taxExemptionRepository,
        private readonly TaxJurisdictionRepository $taxJurisdictionRepository,
    ) {
        parent::__construct($repository);
    }

    public function calculateTax(TaxCalculationRequestDTO $request): TaxCalculationResultDTO
    {
        try {
            $baseAmount = $request->amount;
            $taxAmount = 0.0;
            $totalAmount = 0.0;
            $taxBreakdown = [];
            $appliedTaxes = [];
            $exemptionsApplied = [];
            $jurisdiction = null;
            $calculationMethod = 'standard';

            if ($request->isInclusive) {
                $result = $this->calculateInclusiveTax($request);
            } else {
                $result = $this->calculateExclusiveTax($request);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Tax calculation error: ' . $e->getMessage(), [
                'request' => $request,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new ServiceException('Failed to calculate tax: ' . $e->getMessage());
        }
    }

    private function calculateExclusiveTax(TaxCalculationRequestDTO $request): TaxCalculationResultDTO
    {
        $baseAmount = $request->amount;
        $taxAmount = 0.0;
        $taxBreakdown = [];
        $appliedTaxes = [];
        $exemptionsApplied = [];
        $jurisdiction = null;

        $jurisdiction = $this->determineJurisdiction($request);

        $exemptions = $this->getApplicableExemptions($request);

        if ($request->taxGroupId) {
            $taxGroup = $this->taxGroupRepository->findById($request->taxGroupId, ['*'], ['taxRates']);
            
            if ($taxGroup && $taxGroup->isActive()) {
                $result = $this->calculateGroupTax($baseAmount, $taxGroup, $exemptions);
                $taxAmount = $result['tax_amount'];
                $taxBreakdown = $result['breakdown'];
                $appliedTaxes = $result['applied_taxes'];
                $exemptionsApplied = $result['exemptions'];
            }
        } elseif ($request->taxRateId) {
            $taxRate = TaxRate::find($request->taxRateId);
            
            if ($taxRate && $taxRate->isActive()) {
                $result = $this->calculateSingleTax($baseAmount, $taxRate, $exemptions);
                $taxAmount = $result['tax_amount'];
                $taxBreakdown = $result['breakdown'];
                $appliedTaxes = $result['applied_taxes'];
                $exemptionsApplied = $result['exemptions'];
            }
        } elseif ($jurisdiction && $jurisdiction->taxGroup) {
            $result = $this->calculateGroupTax($baseAmount, $jurisdiction->taxGroup, $exemptions);
            $taxAmount = $result['tax_amount'];
            $taxBreakdown = $result['breakdown'];
            $appliedTaxes = $result['applied_taxes'];
            $exemptionsApplied = $result['exemptions'];
        } elseif ($jurisdiction && $jurisdiction->taxRate) {
            $result = $this->calculateSingleTax($baseAmount, $jurisdiction->taxRate, $exemptions);
            $taxAmount = $result['tax_amount'];
            $taxBreakdown = $result['breakdown'];
            $appliedTaxes = $result['applied_taxes'];
            $exemptionsApplied = $result['exemptions'];
        }

        $totalAmount = $this->roundAmount($baseAmount + $taxAmount);

        return new TaxCalculationResultDTO(
            baseAmount: $this->roundAmount($baseAmount),
            taxAmount: $this->roundAmount($taxAmount),
            totalAmount: $totalAmount,
            isInclusive: false,
            taxBreakdown: $taxBreakdown,
            appliedTaxes: $appliedTaxes,
            exemptionsApplied: $exemptionsApplied,
            jurisdictionId: $jurisdiction?->id,
            calculationMethod: 'exclusive',
        );
    }

    private function calculateInclusiveTax(TaxCalculationRequestDTO $request): TaxCalculationResultDTO
    {
        $totalAmount = $request->amount;
        $jurisdiction = $this->determineJurisdiction($request);
        $exemptions = $this->getApplicableExemptions($request);

        $effectiveTaxRate = 0.0;

        if ($request->taxGroupId) {
            $taxGroup = $this->taxGroupRepository->findById($request->taxGroupId, ['*'], ['taxRates']);
            if ($taxGroup && $taxGroup->isActive()) {
                $effectiveTaxRate = $this->getEffectiveRate($taxGroup, $exemptions);
            }
        } elseif ($request->taxRateId) {
            $taxRate = TaxRate::find($request->taxRateId);
            if ($taxRate && $taxRate->isActive()) {
                $effectiveTaxRate = $this->getEffectiveRateForSingleTax($taxRate, $exemptions);
            }
        } elseif ($jurisdiction && $jurisdiction->taxGroup) {
            $effectiveTaxRate = $this->getEffectiveRate($jurisdiction->taxGroup, $exemptions);
        } elseif ($jurisdiction && $jurisdiction->taxRate) {
            $effectiveTaxRate = $this->getEffectiveRateForSingleTax($jurisdiction->taxRate, $exemptions);
        }

        $baseAmount = $this->roundAmount($totalAmount / (1 + ($effectiveTaxRate / 100)));
        $taxAmount = $this->roundAmount($totalAmount - $baseAmount);

        $taxBreakdown = [];
        $appliedTaxes = [];
        $exemptionsApplied = [];

        if ($request->taxGroupId) {
            $taxGroup = $this->taxGroupRepository->findById($request->taxGroupId, ['*'], ['taxRates']);
            if ($taxGroup) {
                $result = $this->calculateGroupTax($baseAmount, $taxGroup, $exemptions);
                $taxBreakdown = $result['breakdown'];
                $appliedTaxes = $result['applied_taxes'];
                $exemptionsApplied = $result['exemptions'];
            }
        } elseif ($request->taxRateId) {
            $taxRate = TaxRate::find($request->taxRateId);
            if ($taxRate) {
                $result = $this->calculateSingleTax($baseAmount, $taxRate, $exemptions);
                $taxBreakdown = $result['breakdown'];
                $appliedTaxes = $result['applied_taxes'];
                $exemptionsApplied = $result['exemptions'];
            }
        }

        return new TaxCalculationResultDTO(
            baseAmount: $baseAmount,
            taxAmount: $taxAmount,
            totalAmount: $this->roundAmount($totalAmount),
            isInclusive: true,
            taxBreakdown: $taxBreakdown,
            appliedTaxes: $appliedTaxes,
            exemptionsApplied: $exemptionsApplied,
            jurisdictionId: $jurisdiction?->id,
            calculationMethod: 'inclusive',
        );
    }

    private function calculateSingleTax(float $baseAmount, TaxRate $taxRate, array $exemptions): array
    {
        $taxAmount = $this->roundAmount($baseAmount * ($taxRate->rate / 100));
        
        $exemptedAmount = 0.0;
        $exemptionsApplied = [];

        foreach ($exemptions as $exemption) {
            if ($exemption->tax_rate_id === $taxRate->id) {
                $exemptedAmount += $exemption->calculateExemptedAmount($taxAmount);
                $exemptionsApplied[] = [
                    'exemption_id' => $exemption->id,
                    'exemption_type' => $exemption->exemption_type,
                    'exempted_amount' => $exemption->calculateExemptedAmount($taxAmount),
                ];
            }
        }

        $finalTaxAmount = $this->roundAmount($taxAmount - $exemptedAmount);

        return [
            'tax_amount' => $finalTaxAmount,
            'breakdown' => [
                [
                    'tax_rate_id' => $taxRate->id,
                    'tax_name' => $taxRate->name,
                    'tax_rate' => $taxRate->rate,
                    'tax_type' => $taxRate->type,
                    'base_amount' => $baseAmount,
                    'calculated_tax' => $taxAmount,
                    'exempted_amount' => $exemptedAmount,
                    'final_tax' => $finalTaxAmount,
                ],
            ],
            'applied_taxes' => [
                [
                    'tax_rate_id' => $taxRate->id,
                    'tax_name' => $taxRate->name,
                    'rate' => $taxRate->rate,
                    'amount' => $finalTaxAmount,
                ],
            ],
            'exemptions' => $exemptionsApplied,
        ];
    }

    private function calculateGroupTax(float $baseAmount, TaxGroup $taxGroup, array $exemptions): array
    {
        $totalTax = 0.0;
        $breakdown = [];
        $appliedTaxes = [];
        $exemptionsApplied = [];
        $currentBase = $baseAmount;

        $taxRates = $taxGroup->taxRates->sortBy('pivot.sequence');

        foreach ($taxRates as $taxRate) {
            if (!$taxRate->isActive()) {
                continue;
            }

            $applyOnPrevious = $taxRate->pivot->apply_on_previous ?? false;
            
            if ($applyOnPrevious && $taxGroup->application_type === 'compound') {
                $taxableAmount = $currentBase;
            } else {
                $taxableAmount = $baseAmount;
            }

            $taxAmount = $this->roundAmount($taxableAmount * ($taxRate->rate / 100));
            
            $exemptedAmount = 0.0;
            foreach ($exemptions as $exemption) {
                if ($exemption->tax_rate_id === $taxRate->id || $exemption->tax_group_id === $taxGroup->id) {
                    $exemptedAmount += $exemption->calculateExemptedAmount($taxAmount);
                    $exemptionsApplied[] = [
                        'exemption_id' => $exemption->id,
                        'exemption_type' => $exemption->exemption_type,
                        'exempted_amount' => $exemption->calculateExemptedAmount($taxAmount),
                        'tax_rate_id' => $taxRate->id,
                    ];
                }
            }

            $finalTaxAmount = $this->roundAmount($taxAmount - $exemptedAmount);
            $totalTax += $finalTaxAmount;

            $breakdown[] = [
                'tax_rate_id' => $taxRate->id,
                'tax_name' => $taxRate->name,
                'tax_rate' => $taxRate->rate,
                'tax_type' => $taxRate->type,
                'base_amount' => $taxableAmount,
                'calculated_tax' => $taxAmount,
                'exempted_amount' => $exemptedAmount,
                'final_tax' => $finalTaxAmount,
                'is_compound' => $applyOnPrevious,
            ];

            $appliedTaxes[] = [
                'tax_rate_id' => $taxRate->id,
                'tax_name' => $taxRate->name,
                'rate' => $taxRate->rate,
                'amount' => $finalTaxAmount,
            ];

            if ($applyOnPrevious && $taxGroup->application_type === 'compound') {
                $currentBase += $finalTaxAmount;
            }
        }

        if ($taxGroup->application_type === 'highest') {
            $maxTax = collect($breakdown)->max('final_tax') ?? 0.0;
            $totalTax = $maxTax;
        } elseif ($taxGroup->application_type === 'average') {
            $count = count($breakdown);
            $totalTax = $count > 0 ? $this->roundAmount($totalTax / $count) : 0.0;
        }

        return [
            'tax_amount' => $this->roundAmount($totalTax),
            'breakdown' => $breakdown,
            'applied_taxes' => $appliedTaxes,
            'exemptions' => $exemptionsApplied,
        ];
    }

    private function determineJurisdiction(TaxCalculationRequestDTO $request): ?TaxJurisdiction
    {
        if (!$request->countryCode && !$request->stateCode && !$request->cityName && !$request->postalCode) {
            return null;
        }

        return $this->taxJurisdictionRepository->findByLocation(
            $request->countryCode,
            $request->stateCode,
            $request->cityName,
            $request->postalCode
        );
    }

    private function getApplicableExemptions(TaxCalculationRequestDTO $request): array
    {
        $exemptions = [];

        if ($request->customerId) {
            $customerExemptions = $this->taxExemptionRepository->findByCustomer($request->customerId);
            $exemptions = array_merge($exemptions, $customerExemptions->all());
        }

        if ($request->productId) {
            $productExemptions = $this->taxExemptionRepository->findByProduct($request->productId);
            $exemptions = array_merge($exemptions, $productExemptions->all());
        }

        return array_filter($exemptions, fn($exemption) => $exemption->isValidOn() && $exemption->isActive());
    }

    private function getEffectiveRate(TaxGroup $taxGroup, array $exemptions): float
    {
        $totalRate = 0.0;
        $taxRates = $taxGroup->taxRates->sortBy('pivot.sequence');

        foreach ($taxRates as $taxRate) {
            if (!$taxRate->isActive()) {
                continue;
            }

            $rate = $taxRate->rate;
            
            foreach ($exemptions as $exemption) {
                if ($exemption->tax_rate_id === $taxRate->id || $exemption->tax_group_id === $taxGroup->id) {
                    if ($exemption->isFullExemption()) {
                        $rate = 0.0;
                    } elseif ($exemption->isPartialExemption()) {
                        $rate = $rate * (1 - ($exemption->exemption_rate / 100));
                    }
                }
            }

            $totalRate += $rate;
        }

        if ($taxGroup->application_type === 'highest') {
            $totalRate = $taxRates->max('rate') ?? 0.0;
        } elseif ($taxGroup->application_type === 'average') {
            $count = $taxRates->count();
            $totalRate = $count > 0 ? $totalRate / $count : 0.0;
        }

        return $totalRate;
    }

    private function getEffectiveRateForSingleTax(TaxRate $taxRate, array $exemptions): float
    {
        $rate = $taxRate->rate;

        foreach ($exemptions as $exemption) {
            if ($exemption->tax_rate_id === $taxRate->id) {
                if ($exemption->isFullExemption()) {
                    return 0.0;
                } elseif ($exemption->isPartialExemption()) {
                    $rate = $rate * (1 - ($exemption->exemption_rate / 100));
                }
            }
        }

        return $rate;
    }

    private function roundAmount(float $amount, int $precision = self::ROUNDING_PRECISION): float
    {
        return round($amount, $precision);
    }

    public function saveTaxCalculation(
        string $entityType,
        int $entityId,
        TaxCalculationResultDTO $result,
        ?int $customerId = null,
        ?int $productId = null,
        ?int $branchId = null
    ): void {
        DB::transaction(function () use ($entityType, $entityId, $result, $customerId, $productId, $branchId) {
            $this->repository->create([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'base_amount' => $result->baseAmount,
                'tax_amount' => $result->taxAmount,
                'total_amount' => $result->totalAmount,
                'is_inclusive' => $result->isInclusive,
                'tax_breakdown' => $result->taxBreakdown,
                'applied_taxes' => $result->appliedTaxes,
                'exemptions_applied' => $result->exemptionsApplied,
                'customer_id' => $customerId,
                'product_id' => $productId,
                'branch_id' => $branchId,
                'tax_jurisdiction_id' => $result->jurisdictionId,
                'calculation_method' => $result->calculationMethod,
                'calculated_at' => now(),
            ]);
        });
    }

    public function getTaxSummary($startDate = null, $endDate = null): array
    {
        return $this->repository->getTaxSummary($startDate, $endDate);
    }

    public function getTaxBreakdown($startDate = null, $endDate = null)
    {
        return $this->repository->getTaxBreakdown($startDate, $endDate);
    }
}
