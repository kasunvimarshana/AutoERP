<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Contracts\CurrentTenantContextResolverInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Application\DTO\CurrentTenantContext;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Exceptions\CurrentTenantContextResolutionException;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

final class CurrentTenantContextResolver implements CurrentTenantContextResolverInterface
{
    public function __construct(private readonly CurrentUserContextAccessorInterface $currentUser) {}

    public function resolve(Request $request): ?CurrentTenantContext
    {
        $tenants = collect([
            $this->fromId($request->input('tenant_id')),
            $this->fromId($request->headers->get('X-Tenant-ID')),
            $this->fromCode($request->input('tenant_code')),
            $this->fromCode($request->headers->get('X-Tenant-Code')),
            $this->fromId($this->currentUser->currentTenantId()),
            $this->fromId(data_get($request->user(), 'tenant_id')),
        ])->filter();

        if ($tenants->isEmpty()) {
            return null;
        }

        $this->guardAgainstConflicts($tenants);
        $tenant = $tenants->first();

        if (! $tenant instanceof TenantModel) {
            return null;
        }

        if (! $tenant->is_active || $tenant->status !== 'active') {
            throw new CurrentTenantContextResolutionException('The resolved tenant is not active.');
        }

        $domain = DB::table('tenant_domains')
            ->where('tenant_id', $tenant->getKey())
            ->where('status', 'active')
            ->orderByDesc('is_primary')
            ->value('domain');

        return new CurrentTenantContext(
            new DataRecord($tenant->attributesToArray()),
            (int) $tenant->getKey(),
            (string) $tenant->code,
            (string) $tenant->uuid,
            $tenant->isolation_key,
            is_string($domain) ? $domain : null,
            (string) $tenant->status,
            (bool) $tenant->is_active,
            $this->applicationId($request),
            'request_or_authenticated_user',
        );
    }

    public function hasAccess(Request $request, CurrentTenantContext $context): bool
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return false;
        }

        $userTenantId = $this->positiveInt(data_get($user, 'tenant_id'));
        if ($userTenantId === $context->tenantId()) {
            return true;
        }

        return DB::table('user_tenants')
            ->where('tenant_id', $context->tenantId())
            ->where('user_id', $user->getAuthIdentifier())
            ->exists();
    }

    private function fromId(mixed $value): ?TenantModel
    {
        $id = $this->positiveInt($value);

        return $id === null ? null : TenantModel::query()->find($id);
    }

    private function fromCode(mixed $value): ?TenantModel
    {
        if (! is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        return TenantModel::query()
            ->where('code', trim((string) $value))
            ->first();
    }

    /**
     * @param  Collection<int, TenantModel>  $tenants
     */
    private function guardAgainstConflicts(Collection $tenants): void
    {
        if ($tenants->pluck('id')->unique()->count() > 1) {
            throw new CurrentTenantContextResolutionException(
                'Requested tenant metadata resolved to multiple tenants.',
            );
        }
    }

    private function applicationId(Request $request): ?string
    {
        foreach (['X-Application-Id', 'X-App-Id', 'X-Client-Id'] as $header) {
            $value = $request->headers->get($header);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return $this->currentUser->currentApplicationId();
    }

    private function positiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }
}
