<?php

declare(strict_types=1);

namespace Modules\User\Services\Authentication;

use Modules\Core\Contracts\PlatformPermissionDirectoryInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\User\Constants\PlatformOperatorStatus;
use Modules\User\Contracts\PlatformOperatorAuthenticationDirectoryInterface;
use Modules\User\Models\PlatformOperatorModel;

final class PlatformOperatorAuthenticationDirectory implements PlatformOperatorAuthenticationDirectoryInterface
{
    public function __construct(
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly PlatformPermissionDirectoryInterface $permissions,
    ) {}

    public function findPlatformForLogin(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        return $this->executionContext->runAsControlPlane(function () use ($email): ?array {
            $operator = PlatformOperatorModel::query()->where('email', $email)->first();

            return $operator instanceof PlatformOperatorModel ? $this->record($operator) : null;
        });
    }

    public function findActivePlatformById(int $operatorId): ?array
    {
        if ($operatorId < 1) {
            return null;
        }

        return $this->executionContext->runAsControlPlane(function () use ($operatorId): ?array {
            $operator = PlatformOperatorModel::query()
                ->whereKey($operatorId)
                ->where('status', PlatformOperatorStatus::ACTIVE)
                ->whereNotNull('credentials_ready_at')
                ->first();

            return $operator instanceof PlatformOperatorModel ? $this->record($operator) : null;
        });
    }

    public function summariesByIds(array $operatorIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, $operatorIds),
            static fn (int $id): bool => $id > 0,
        )));
        if ($ids === []) {
            return [];
        }

        return $this->executionContext->runAsControlPlane(function () use ($ids): array {
            return PlatformOperatorModel::query()
                ->whereIn('id', $ids)
                ->get()
                ->mapWithKeys(fn (PlatformOperatorModel $operator): array => [
                    (int) $operator->getKey() => $this->record($operator),
                ])
                ->all();
        });
    }

    public function permissionNames(int $operatorId): array
    {
        return $this->permissions->permissions($operatorId);
    }

    /** @return array{id:int,first_name:string,last_name:?string,email:string,status:string,credentials_ready:bool} */
    private function record(PlatformOperatorModel $operator): array
    {
        return [
            'id' => (int) $operator->getKey(),
            'first_name' => (string) $operator->getAttribute('first_name'),
            'last_name' => $this->nullableString($operator->getAttribute('last_name')),
            'email' => (string) $operator->getAttribute('email'),
            'status' => (string) $operator->getAttribute('status'),
            'credentials_ready' => $operator->getAttribute('credentials_ready_at') !== null,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
