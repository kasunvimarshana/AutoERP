<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Configuration\Domain\Aggregates\ConfigurationRecordAggregate;
use Modules\Configuration\Domain\Contracts\ConfigurationWriteRepositoryContract;
use Modules\Configuration\Domain\Enums\ConfigurationRecordType;
use Modules\Configuration\Domain\Exceptions\ConfigurationConflictException;
use Modules\Configuration\Domain\Exceptions\ConfigurationRecordNotFoundException;

class EloquentConfigurationWriteRepository implements ConfigurationWriteRepositoryContract
{
    public function upsert(ConfigurationRecordAggregate $aggregate): Model
    {
        return DB::transaction(function () use ($aggregate): Model {
            $query = $this->newQuery($aggregate->type(), withTrashed: true);

            if ($aggregate->id() === null) {
                /** @var Model $model */
                $model = new ($aggregate->type()->modelClass())();
                $model->fill($aggregate->payload());
                $model->save();

                return $model;
            }

            /** @var Model|null $model */
            $model = $query->whereKey($aggregate->id())->lockForUpdate()->first();
            if ($model === null) {
                throw ConfigurationRecordNotFoundException::forId($aggregate->type(), (int) $aggregate->id());
            }

            if ($aggregate->expectedRowVersion() === null) {
                throw new ConfigurationConflictException('expected_row_version is required for updates.');
            }

            $currentVersion = (int) ($model->getAttribute('row_version') ?? 1);
            if ($currentVersion !== $aggregate->expectedRowVersion()) {
                throw new ConfigurationConflictException(sprintf(
                    'Stale update rejected. expected_row_version=%d current_row_version=%d',
                    $aggregate->expectedRowVersion(),
                    $currentVersion,
                ));
            }

            $payload = $aggregate->payload();
            $payload['row_version'] = $currentVersion + 1;

            $model->fill($payload);
            $model->save();

            return $model;
        });
    }

    public function delete(ConfigurationRecordType $type, int $id): void
    {
        DB::transaction(function () use ($type, $id): void {
            /** @var Model|null $model */
            $model = $this->newQuery($type)->whereKey($id)->lockForUpdate()->first();
            if ($model === null) {
                throw ConfigurationRecordNotFoundException::forId($type, $id);
            }

            $model->delete();
        });
    }

    public function dependencyCounts(ConfigurationRecordType $type, int $id): array
    {
        $result = [];

        foreach ($this->dependencyMap($type) as $table => $column) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $count = (int) DB::table($table)->where($column, $id)->count();
            if ($count > 0) {
                $result[$table] = $count;
            }
        }

        return $result;
    }

    /**
     * @return array<string, string>
     */
    private function dependencyMap(ConfigurationRecordType $type): array
    {
        return match ($type) {
            ConfigurationRecordType::Country => [
                'customer_addresses' => 'country_id',
                'supplier_addresses' => 'country_id',
                'employees' => 'country_id',
            ],
            ConfigurationRecordType::Currency => [
                'tenant_plans' => 'currency_id',
                'tenants' => 'currency_id',
                'customers' => 'currency_id',
                'suppliers' => 'currency_id',
                'purchase_orders' => 'currency_id',
                'grn_headers' => 'currency_id',
                'purchase_returns' => 'currency_id',
                'sales_orders' => 'currency_id',
                'gdn_headers' => 'currency_id',
                'sales_returns' => 'currency_id',
                'invoices' => 'currency_id',
                'invoice_references' => 'currency_id',
                'price_lists' => 'currency_id',
                'payments' => 'currency_id',
                'accounts' => 'currency_id',
                'ap_transactions' => 'currency_id',
                'ar_transactions' => 'currency_id',
                'journal_entry_lines' => 'currency_id',
                'bank_accounts' => 'currency_id',
                'employee_contracts' => 'currency_id',
                'vehicle_service_job_cards' => 'currency_id',
            ],
            default => [],
        };
    }

    private function newQuery(ConfigurationRecordType $type, bool $withTrashed = false): Builder
    {
        $modelClass = $type->modelClass();
        /** @var Builder $query */
        $query = $modelClass::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query;
    }
}
