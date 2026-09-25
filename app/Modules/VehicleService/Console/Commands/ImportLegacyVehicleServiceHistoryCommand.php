<?php

declare(strict_types=1);

namespace Modules\VehicleService\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;
use Modules\VehicleService\Services\Import\LegacyVehicleServiceHistoryImporter;
use RuntimeException;

final class ImportLegacyVehicleServiceHistoryCommand extends Command
{
    protected $signature = 'vehicle-service:import-legacy-history
        {source : Absolute path to the untrusted legacy AutoERP SQL dump}
        {--tenant=AUTOERP : Target tenant ID or code}
        {--organization= : Target organization-unit ID or code; defaults to global scope}
        {--apply : Apply the import; without this option the command is read-only}';

    protected $description = 'Dry-run or import immutable legacy vehicle service history without executing the source SQL dump.';

    public function handle(LegacyVehicleServiceHistoryImporter $importer, TenantExecutionContextInterface $executionContext): int
    {
        $tenant = $this->resolveTenant((string) $this->option('tenant'));
        $apply = (bool) $this->option('apply');
        $tenantId = (int) $tenant->getKey();
        $result = $executionContext->runForTenant($tenantId, function () use ($importer, $tenant, $tenantId, $apply): array {
            $organization = $this->resolveOrganization($tenant, (string) $this->option('organization'));

            return $importer->execute(
                (string) $this->argument('source'),
                $tenantId,
                $organization?->getKey() === null ? null : (int) $organization->getKey(),
                $apply,
            );
        });

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        if ($result['mode'] === 'blocked') {
            $this->error('The import is blocked. Resolve every reported conflict and run the dry run again.');

            return self::FAILURE;
        }
        if (! $apply) {
            $this->info('Dry run only: no database records were changed. Add --apply only after reviewing this result and taking a backup.');
        } elseif ($result['mode'] === 'applied') {
            $this->info('Legacy vehicle service history was imported atomically.');
        } else {
            $this->info('This exact source was already imported; no duplicate records were created.');
        }

        return self::SUCCESS;
    }

    private function resolveTenant(string $reference): TenantModel
    {
        $query = TenantModel::query();
        $tenant = ctype_digit($reference)
            ? $query->find((int) $reference)
            : $query->where('code', $reference)->first();

        if (! $tenant instanceof TenantModel) {
            throw new RuntimeException("Target tenant '{$reference}' was not found.");
        }

        return $tenant;
    }

    private function resolveOrganization(TenantModel $tenant, string $reference): ?OrganizationUnitModel
    {
        if (trim($reference) === '') {
            return null;
        }

        $query = OrganizationUnitModel::query()->where('tenant_id', $tenant->getKey());
        $organization = ctype_digit($reference)
            ? $query->find((int) $reference)
            : $query->where('code', $reference)->first();

        if (! $organization instanceof OrganizationUnitModel) {
            throw new RuntimeException("Target organization unit '{$reference}' was not found in the selected tenant.");
        }

        return $organization;
    }
}
