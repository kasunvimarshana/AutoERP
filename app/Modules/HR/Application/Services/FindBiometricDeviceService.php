<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\HR\Application\Contracts\FindBiometricDeviceServiceInterface;
use Modules\HR\Domain\Entities\BiometricDevice;
use Modules\HR\Domain\RepositoryInterfaces\BiometricDeviceRepositoryInterface;

class FindBiometricDeviceService implements FindBiometricDeviceServiceInterface
{
    /** @var list<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'status', 'org_unit_id', 'device_type', 'code'];

    /** @var list<string> */
    private const ALLOWED_SORTS = ['id', 'name', 'code', 'device_type', 'status', 'created_at', 'updated_at'];

    /** @var list<string> */
    private const ALLOWED_INCLUDES = ['orgUnit'];

    public function __construct(
        private readonly BiometricDeviceRepositoryInterface $deviceRepository,
    ) {}

    public function find(mixed $id): ?BiometricDevice
    {
        return $this->deviceRepository->find($id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null,
    ): LengthAwarePaginator {
        $repository = $this->deviceRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }
            $repository->where($field, $value);
        }

        [$sortField, $sortDirection] = $this->parseSort($sort);
        if ($sortField !== null) {
            $repository->orderBy($sortField, $sortDirection);
        }

        $relations = $this->parseIncludes($include);
        if ($relations !== []) {
            $repository->with($relations);
        }

        return $repository->paginate($perPage ?? 15, ['*'], 'page', $page);
    }

    /** @return array{0: string|null, 1: string} */
    private function parseSort(?string $sort): array
    {
        if ($sort === null || $sort === '') {
            return [null, 'asc'];
        }

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        return in_array($field, self::ALLOWED_SORTS, true) ? [$field, $direction] : [null, 'asc'];
    }

    /** @return list<string> */
    private function parseIncludes(?string $include): array
    {
        if ($include === null || $include === '') {
            return [];
        }

        $relations = array_filter(array_map('trim', explode(',', $include)));

        return array_values(array_filter(
            $relations,
            fn (string $r): bool => in_array($r, self::ALLOWED_INCLUDES, true)
        ));
    }
}
