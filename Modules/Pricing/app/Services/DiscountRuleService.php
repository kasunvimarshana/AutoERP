<?php

declare(strict_types=1);

namespace Modules\Pricing\Services;

use App\Core\Services\BaseService;
use Illuminate\Validation\ValidationException;
use Modules\Pricing\Repositories\DiscountRuleRepository;

/**
 * DiscountRule Service
 *
 * Contains business logic for DiscountRule operations
 */
class DiscountRuleService extends BaseService
{
    public function __construct(DiscountRuleRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new discount rule
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

        if (! isset($data['usage_count'])) {
            $data['usage_count'] = 0;
        }

        return parent::create($data);
    }

    /**
     * Update discount rule
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
     * Get active rules
     */
    public function getActive(): mixed
    {
        return $this->repository->getActive();
    }

    /**
     * Search rules
     */
    public function search(string $query): mixed
    {
        return $this->repository->search($query);
    }
}
