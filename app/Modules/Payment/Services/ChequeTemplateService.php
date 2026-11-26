<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Modules\Payment\Models\ChequeTemplate;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class ChequeTemplateService
{
    /**
     * @return LengthAwarePaginator<ChequeTemplate>
     */
    public function paginate(
        int $tenantId,
        ?int $organizationUnitId,
        ?bool $isActive = null,
        ?string $search = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = $this->scope(ChequeTemplate::query(), $tenantId, $organizationUnitId);
        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }
        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('template_name', 'like', $term)
                    ->orWhere('bank_name', 'like', $term);
            });
        }

        return $query->orderByDesc('is_default')
            ->orderBy('bank_name')
            ->orderBy('template_name')
            ->paginate(max(1, min(100, $perPage)));
    }

    /** @return Collection<int, ChequeTemplate> */
    public function list(int $tenantId, ?int $organizationUnitId, ?bool $isActive = null): Collection
    {
        $query = $this->scope(ChequeTemplate::query(), $tenantId, $organizationUnitId);
        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        return $query->orderByDesc('is_default')->orderBy('bank_name')->orderBy('template_name')->get();
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): ChequeTemplate
    {
        return $this->scope(ChequeTemplate::query(), $tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function create(array $payload, int $tenantId, ?int $organizationUnitId): ChequeTemplate
    {
        return DB::transaction(function () use ($payload, $tenantId, $organizationUnitId): ChequeTemplate {
            $attributes = $this->attributes($payload);
            $attributes['tenant_id'] = $tenantId;
            $attributes['organization_unit_id'] = $organizationUnitId;
            $attributes['row_version'] = 1;
            $attributes['is_default'] = (bool) ($attributes['is_default'] ?? false);
            $attributes['is_active'] = (bool) ($attributes['is_active'] ?? true);
            if ($attributes['is_default'] && ! $attributes['is_active']) {
                throw new InvalidArgumentException('Inactive cheque templates cannot be default templates.');
            }
            $attributes['default_scope_key'] = $attributes['is_default'] ? $this->scopeKey($tenantId, $organizationUnitId) : null;
            if ($attributes['is_default']) {
                $this->clearDefault($tenantId, $organizationUnitId);
            }

            return ChequeTemplate::query()->create($attributes);
        });
    }

    public function update(ChequeTemplate $template, array $payload, ?int $expectedVersion = null): ChequeTemplate
    {
        return DB::transaction(function () use ($template, $payload, $expectedVersion): ChequeTemplate {
            $template = ChequeTemplate::query()->lockForUpdate()->findOrFail($template->getKey());

            if ($expectedVersion !== null && (int) $template->row_version !== $expectedVersion) {
                throw new ConflictHttpException('Cheque template was changed by someone else. Reload before saving.');
            }

            $attributes = $this->attributes($payload);
            if (array_key_exists('is_default', $attributes)) {
                $attributes['is_default'] = (bool) $attributes['is_default'];
            }
            if (array_key_exists('is_active', $attributes)) {
                $attributes['is_active'] = (bool) $attributes['is_active'];
            }

            $willBeDefault = array_key_exists('is_default', $attributes) ? (bool) $attributes['is_default'] : (bool) $template->is_default;
            $willBeActive = array_key_exists('is_active', $attributes) ? (bool) $attributes['is_active'] : (bool) $template->is_active;
            if ($willBeDefault && ! $willBeActive) {
                throw new InvalidArgumentException('Inactive cheque templates cannot be default templates.');
            }
            if (! $willBeDefault && (bool) $template->is_default) {
                throw new InvalidArgumentException('Select another default cheque template before clearing this default.');
            }
            $attributes['default_scope_key'] = $willBeDefault ? $this->scopeKey((int) $template->tenant_id, $template->organization_unit_id) : null;
            if ($willBeDefault) {
                $this->clearDefault((int) $template->tenant_id, $template->organization_unit_id, (int) $template->getKey());
            }

            $attributes['row_version'] = ((int) $template->row_version) + 1;
            $template->fill($attributes)->save();

            return $template->refresh();
        });
    }

    public function delete(ChequeTemplate $template): void
    {
        if ((bool) $template->is_default) {
            throw new LogicException('The default cheque template cannot be deleted. Select another default cheque template first.');
        }
        if ($template->printLogs()->exists()) {
            throw new LogicException('Cheque templates with print history cannot be deleted. Deactivate the template instead.');
        }

        $template->delete();
    }

    public function resolveActive(int $tenantId, ?int $organizationUnitId, ?int $templateId = null): ChequeTemplate
    {
        $query = $this->scope(ChequeTemplate::query(), $tenantId, $organizationUnitId)->where('is_active', true);
        if ($templateId !== null) {
            return $query->findOrFail($templateId);
        }

        $template = $query->orderByDesc('is_default')->orderByRaw('organization_unit_id is null')->orderBy('id')->first();
        if (! $template instanceof ChequeTemplate) {
            throw new InvalidArgumentException('An active cheque template is required.');
        }

        return $template;
    }

    private function scope(Builder $query, int $tenantId, ?int $organizationUnitId): Builder
    {
        $query->where('tenant_id', $tenantId);
        if ($organizationUnitId === null) {
            return $query->whereNull('organization_unit_id');
        }

        return $query->where(function (Builder $scope) use ($organizationUnitId): void {
            $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
        });
    }

    private function clearDefault(int $tenantId, ?int $organizationUnitId, ?int $exceptId = null): void
    {
        $query = ChequeTemplate::query()->where('tenant_id', $tenantId)->lockForUpdate();
        $organizationUnitId === null ? $query->whereNull('organization_unit_id') : $query->where('organization_unit_id', $organizationUnitId);
        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }
        $query->update(['is_default' => false, 'default_scope_key' => null]);
    }

    private function scopeKey(int $tenantId, ?int $organizationUnitId): string
    {
        return $organizationUnitId === null ? 'tenant:'.$tenantId : 'org:'.$tenantId.':'.$organizationUnitId;
    }

    private function attributes(array $payload): array
    {
        unset($payload['id'], $payload['tenant_id'], $payload['organization_unit_id'], $payload['expected_version']);

        return $payload;
    }
}
