<?php

namespace Modules\POS\Application\UseCases;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\POS\Domain\Contracts\PosDiscountRepositoryInterface;
use Modules\POS\Domain\Events\PosDiscountCreated;
use Modules\Shared\Domain\Contracts\UseCaseInterface;

class CreatePosDiscountUseCase implements UseCaseInterface
{
    public function __construct(
        private PosDiscountRepositoryInterface $discountRepo,
    ) {}

    public function execute(array $data): mixed
    {
        $code     = strtoupper(trim($data['code'] ?? ''));
        $name     = trim($data['name'] ?? '');
        $type     = $data['type'] ?? 'percentage';
        $value    = (string) ($data['value'] ?? '0');
        $tenantId = $data['tenant_id'];

        if ($code === '') {
            throw new DomainException('Discount code must not be empty.');
        }

        if ($name === '') {
            throw new DomainException('Discount name must not be empty.');
        }

        if (bccomp($value, '0', 8) <= 0) {
            throw new DomainException('Discount value must be greater than zero.');
        }

        if ($type === 'percentage' && bccomp($value, '100', 8) > 0) {
            throw new DomainException('Percentage discount cannot exceed 100.');
        }

        if ($this->discountRepo->findByCode($tenantId, $code) !== null) {
            throw new DomainException('A discount with this code already exists.');
        }

        return DB::transaction(function () use ($data, $tenantId, $code, $name, $type, $value) {
            $discount = $this->discountRepo->create([
                'id'          => (string) Str::uuid(),
                'tenant_id'   => $tenantId,
                'code'        => $code,
                'name'        => $name,
                'type'        => $type,
                'value'       => bcadd($value, '0.00000000', 8),
                'usage_limit' => isset($data['usage_limit']) ? (int) $data['usage_limit'] : null,
                'times_used'  => 0,
                'expires_at'  => $data['expires_at'] ?? null,
                'is_active'   => (bool) ($data['is_active'] ?? true),
                'description' => $data['description'] ?? null,
            ]);

            Event::dispatch(new PosDiscountCreated(
                discountId: $discount->id,
                tenantId:   $tenantId,
                code:       $code,
                name:       $name,
            ));

            return $discount;
        });
    }
}
