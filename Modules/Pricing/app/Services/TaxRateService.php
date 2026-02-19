<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use App\Core\Services\BaseService;
use Illuminate\Validation\ValidationException;
use Modules\Pricing\Repositories\TaxRateRepository;

/**
 * TaxRate Service
 *
 * Contains business logic for TaxRate operations
 */
class TaxRateService extends BaseService
{
    public function __construct(TaxRateRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new tax rate
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(array $data): mixed
    {
        if (isset($data['code']) && $this->repository->codeExists($data['code'])) {
            throw ValidationException::withMessages([
                'code' => ['The code has already been taken.'],
            ]);
        }

        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        if (! isset($data['priority'])) {
            $data['priority'] = 0;
        }

        return parent::create($data);
    }

    /**
     * Update tax rate
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(int $id, array $data): mixed
    {
        if (isset($data['code']) && $this->repository->codeExists($data['code'], $id)) {
            throw ValidationException::withMessages([
                'code' => ['The code has already been taken.'],
            ]);
        }

        return parent::update($id, $data);
    }

    /**
     * Get active tax rates
     */
    public function getActive(): mixed
    {
        return $this->repository->getActive();
    }

    /**
     * Get effective tax rates
     */
    public function getEffective(): mixed
    {
        return $this->repository->getEffective();
    }

    /**
     * Calculate tax for amount
     *
     * @return array<string, mixed>
     */
    public function calculateTax(string $amount, string $jurisdiction, ?string $productCategory = null, bool $inclusive = false): array
    {
        $taxRate = null;

        if ($jurisdiction && $productCategory) {
            $taxRate = $this->repository->findForJurisdictionAndCategory($jurisdiction, $productCategory);
        } elseif ($jurisdiction) {
            $taxRate = $this->repository->findForJurisdiction($jurisdiction);
        } elseif ($productCategory) {
            $taxRate = $this->repository->findForProductCategory($productCategory);
        }

        if (! $taxRate) {
            return [
                'tax_amount' => '0.00',
                'tax_rate' => null,
                'taxable_amount' => $amount,
            ];
        }

        $taxAmount = $taxRate->calculateTax($amount, $inclusive);

        return [
            'tax_id' => $taxRate->id,
            'tax_name' => $taxRate->name,
            'tax_code' => $taxRate->code,
            'tax_rate' => (string) $taxRate->rate,
            'tax_amount' => $taxAmount,
            'taxable_amount' => $amount,
            'is_compound' => $taxRate->is_compound,
        ];
    }

    /**
     * Search tax rates
     */
    public function search(string $query): mixed
    {
        return $this->repository->search($query);
    }
}
