<?php

declare(strict_types=1);

namespace Modules\Voucher\Application\Services;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Voucher\Application\DTOs\VoucherRecordData;
use Modules\Voucher\Application\Repositories\RecurringVoucherRepositoryInterface;
use Modules\Voucher\Application\Repositories\VoucherRepositoryInterface;
use Modules\Voucher\Domain\Exceptions\VoucherIntegrityException;
use Modules\Voucher\Domain\Exceptions\VoucherRecordNotFoundException;
use Modules\Voucher\Domain\Services\VoucherDomainService;

class VoucherService
{
    public function __construct(
        private readonly Container $container,
        private readonly TenantRepositoryInterface $tenants,
        private readonly VoucherRepositoryInterface $vouchers,
        private readonly RecurringVoucherRepositoryInterface $recurringVouchers,
        private readonly VoucherDomainService $domain,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function definition(string $resource): array
    {
        $key = $this->domain->normalizeResourceKey($resource);
        $definition = config("voucher.resources.{$key}");

        if (! is_array($definition)) {
            throw VoucherRecordNotFoundException::for('Voucher resource', $resource);
        }

        return ['key' => $key, ...$definition];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(string $resource, int|string $tenantId, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->ensureTenantExists($tenantId);
        $repository = $this->repository($resource);
        $criteria = ['tenant_id' => $tenantId, ...$filters];

        return $perPage === null
            ? $repository->getWhere($criteria)
            : $repository->paginateWhere($criteria, $perPage);
    }

    public function find(string $resource, int|string $tenantId, int|string $id): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = method_exists($repository, 'findForTenantById')
            ? $repository->findForTenantById($tenantId, $id)
            : $repository->getWhere(['tenant_id' => $tenantId, 'id' => $id])->first();

        if ($record === null) {
            throw VoucherRecordNotFoundException::for($definition['label'] ?? $resource, $id);
        }

        return $record;
    }

    public function create(string $resource, VoucherRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $this->ensureTenantExists($data->tenantId);

        return $repository->transaction(fn (): Model => $repository->create($this->prepareAttributes($definition['key'], $data->attributes, $data->tenantId)));
    }

    public function update(string $resource, int|string $tenantId, int|string $id, VoucherRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);
        $this->domain->assertRowVersion($data->rowVersion, $record);

        return $repository->transaction(fn (): Model => $repository->update($record, [
            ...$this->prepareAttributes($definition['key'], $data->attributes, $tenantId),
            'row_version' => $this->domain->nextRowVersion($record),
        ]));
    }

    public function delete(string $resource, int|string $tenantId, int|string $id): bool
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);

        return $repository->transaction(fn (): bool => $repository->delete($record));
    }

    public function post(int|string $tenantId, int|string $id): Model
    {
        $voucher = $this->find('vouchers', $tenantId, $id);
        $this->domain->ensureMutable('vouchers', $voucher, $this->definition('vouchers'), true);

        return $this->vouchers->transaction(fn (): Model => $this->vouchers->update($voucher, [
            'status' => config('voucher.statuses.1', 'POSTED'),
            'row_version' => $this->domain->nextRowVersion($voucher),
        ]));
    }

    public function generateFromRecurring(int|string $tenantId, int|string $id, ?string $voucherNumber = null): Model
    {
        $template = $this->find('recurring_vouchers', $tenantId, $id);

        if (! (bool) $template->is_active) {
            throw VoucherIntegrityException::rule('Recurring voucher is inactive.');
        }

        return $this->recurringVouchers->transaction(function () use ($template, $tenantId, $voucherNumber): Model {
            $voucher = $this->vouchers->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $template->organization_unit_id,
                'metadata' => $template->metadata,
                'voucher_number' => $voucherNumber ?? $this->generatedVoucherNumber($template),
                'type' => $template->type,
                'sub_type' => $template->sub_type,
                'voucher_date' => $template->next_run_date,
                'due_date' => null,
                'party_type' => $template->party_type,
                'party_id' => $template->party_id,
                'reference' => $template->reference,
                'description' => $template->description,
                'account_id' => $template->account_id,
                'contra_account_id' => $template->contra_account_id,
                'tax_rate_id' => $template->tax_rate_id,
                'tax_rate' => $template->tax_rate,
                'amount' => $template->amount,
                'tax_amount' => $template->tax_amount,
                'total_amount' => $template->total_amount,
                'status' => config('voucher.statuses.0', 'DRAFT'),
                'created_by' => $template->created_by,
            ]);

            $this->recurringVouchers->update($template, [
                'next_run_date' => $this->domain->nextRunDate($template->frequency, (int) $template->interval, $template->next_run_date->toDateString()),
                'row_version' => $this->domain->nextRowVersion($template),
            ]);

            return $voucher;
        });
    }

    private function ensureTenantExists(int|string $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw VoucherRecordNotFoundException::for('Tenant', $tenantId);
        }
    }

    private function repository(string $resource): BaseRepositoryInterface
    {
        $definition = $this->definition($resource);
        $repository = $this->container->make($definition['repository']);

        if (! $repository instanceof BaseRepositoryInterface) {
            throw VoucherIntegrityException::rule("Repository for [{$definition['key']}] must implement BaseRepositoryInterface.");
        }

        return $repository;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributes(string $resource, array $attributes, int|string $tenantId): array
    {
        unset($attributes['row_version']);

        $attributes = [
            ...$this->normalizeScalars($attributes),
            'tenant_id' => $tenantId,
        ];
        $attributes['metadata'] = $this->domain->normalizeMetadata($attributes['metadata'] ?? null);
        $attributes['type'] = $this->domain->normalizeEnum('type', $attributes['type'] ?? null, config('voucher.types', []), config('voucher.types.0', 'expense'));

        if (($attributes['sub_type'] ?? null) !== null) {
            $attributes['sub_type'] = $this->domain->normalizeEnum('sub_type', $attributes['sub_type'], config('voucher.sub_types', []), null);
        }

        $attributes = $this->domain->prepareMoneyAttributes($attributes);

        return match ($resource) {
            'vouchers' => $this->prepareVoucherAttributes($attributes),
            'recurring_vouchers' => $this->prepareRecurringVoucherAttributes($attributes),
            default => $attributes,
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeScalars(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            if (is_string($value)) {
                $attributes[$key] = $this->domain->normalizeText($value);
            }
        }

        foreach (['amount', 'tax_amount', 'tax_rate', 'total_amount'] as $column) {
            if (array_key_exists($column, $attributes) && $attributes[$column] !== null) {
                $attributes[$column] = $this->domain->normalizeDecimal($attributes[$column]);
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareVoucherAttributes(array $attributes): array
    {
        $attributes['status'] = $this->domain->normalizeEnum('status', $attributes['status'] ?? null, config('voucher.statuses', []), config('voucher.statuses.0', 'DRAFT'), true);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareRecurringVoucherAttributes(array $attributes): array
    {
        $attributes['frequency'] = $this->domain->normalizeEnum('frequency', $attributes['frequency'] ?? null, config('voucher.frequencies', []), config('voucher.frequencies.2', 'monthly'));
        $attributes['interval'] = max(1, (int) ($attributes['interval'] ?? 1));
        $attributes['is_active'] = $attributes['is_active'] ?? true;

        return $attributes;
    }

    private function generatedVoucherNumber(Model $template): string
    {
        return Str::upper('RV-'.$template->getKey().'-'.now()->format('YmdHis'));
    }
}

