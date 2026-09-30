<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services\Import;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Customer\DTOs\CreateCustomerData;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Models\Customer;
use Modules\Customer\Services\CustomerCreationService;
use Modules\Item\Models\Item;
use Modules\UOM\Models\UnitOfMeasureModel;
use Modules\Vehicle\Data\VehicleOwnershipDraftData;
use Modules\Vehicle\DTOs\CreateVehicleData;
use Modules\Vehicle\Enums\VehicleFuelType;
use Modules\Vehicle\Enums\VehicleOwnershipType;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Enums\VehicleTransmissionType;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Services\VehicleCreationService;
use Modules\VehicleService\Enums\VehicleServiceJobType;
use Modules\VehicleService\Models\VehicleServiceLegacyHistory;
use Modules\VehicleService\Models\VehicleServiceLegacyHistoryItem;
use Modules\VehicleService\Models\VehicleServiceLegacyImportBatch;
use RuntimeException;
use Throwable;

final class LegacyVehicleServiceHistoryImporter
{
    private const SOURCE_SYSTEM = 'old_autoerp';

    private const SOURCE_TABLE = 'vehicle_service_jobs';

    private const LOCK_SECONDS = 30;

    private const ALLOWED_TABLES = [
        'customers',
        'vehicles',
        'vehicle_ownerships',
        'vehicle_service_jobs',
        'vehicle_service_job_lines',
        'items',
        'unit_of_measures',
    ];

    public function __construct(
        private readonly LegacySqlDumpReader $reader,
        private readonly CustomerCreationService $customers,
        private readonly VehicleCreationService $vehicles,
    ) {}

    /** @return array<string, mixed> */
    public function execute(string $path, int $tenantId, ?int $organizationUnitId, bool $apply): array
    {
        $analysis = $this->analyze($path, $tenantId, $organizationUnitId);
        if (! $apply || $analysis['already_imported']) {
            return $this->publicResult($analysis, $apply ? 'already_imported' : 'dry_run');
        }
        if ($analysis['conflicts'] !== []) {
            return $this->publicResult($analysis, 'blocked');
        }

        $lock = Cache::lock($this->lockKey($tenantId, $organizationUnitId), self::LOCK_SECONDS);

        try {
            return $lock->block(self::LOCK_SECONDS, function () use ($path, $tenantId, $organizationUnitId): array {
                return DB::transaction(function () use ($path, $tenantId, $organizationUnitId): array {
                    $analysis = $this->analyze($path, $tenantId, $organizationUnitId, true);
                    if ($analysis['already_imported']) {
                        return $this->publicResult($analysis, 'already_imported');
                    }
                    if ($analysis['conflicts'] !== []) {
                        throw new RuntimeException("The legacy import was blocked after its locked recheck:\n- ".implode("\n- ", $analysis['conflicts']));
                    }

                    return $this->apply($analysis, $tenantId, $organizationUnitId);
                }, 3);
            });
        } catch (Throwable $exception) {
            throw new RuntimeException('Legacy vehicle history import failed atomically: '.$exception->getMessage(), previous: $exception);
        }
    }

    /** @return array<string, mixed> */
    private function analyze(string $path, int $tenantId, ?int $organizationUnitId, bool $lockRows = false): array
    {
        $sourceSha256 = hash_file('sha256', $path);
        if (! is_string($sourceSha256)) {
            throw new RuntimeException('The legacy SQL dump could not be fingerprinted.');
        }

        $scopeKey = $this->scopeKey($organizationUnitId);
        $alreadyImported = VehicleServiceLegacyImportBatch::query()
            ->where('tenant_id', $tenantId)
            ->where('organization_scope_key', $scopeKey)
            ->where('source_system', self::SOURCE_SYSTEM)
            ->where('source_sha256', $sourceSha256)
            ->where('status', 'completed')
            ->exists();

        $source = $this->reader->read($path, self::ALLOWED_TABLES);
        $jobs = array_values($source['vehicle_service_jobs']);
        $sourceVehicles = $this->keyById($source['vehicles']);
        $sourceCustomers = $this->keyById($source['customers']);
        $sourceItems = $this->keyById($source['items']);
        $sourceUoms = $this->keyById($source['unit_of_measures']);
        $ownershipsByVehicle = $this->groupBy($source['vehicle_ownerships'], 'vehicle_id');
        $linesByJob = $this->groupBy($source['vehicle_service_job_lines'], 'vehicle_service_job_id');

        $vehicleQuery = Vehicle::query()->withTrashed()->where('tenant_id', $tenantId);
        $customerQuery = Customer::query()->withTrashed()->where('tenant_id', $tenantId);
        if ($lockRows) {
            $vehicleQuery->lockForUpdate();
            $customerQuery->lockForUpdate();
        }
        $currentVehicles = $vehicleQuery->get();
        $currentCustomers = $customerQuery->get();
        $vehicleIndexes = $this->vehicleIndexes($currentVehicles->all());
        $customerIndexes = $this->customerIndexes($currentCustomers->all());
        $itemByCode = Item::query()->withTrashed()->where('tenant_id', $tenantId)->get()->keyBy(fn (Item $item): string => $this->normalizeCode($item->code))->all();
        $uomByCode = UnitOfMeasureModel::query()->withTrashed()->where('tenant_id', $tenantId)->get()->keyBy(fn (UnitOfMeasureModel $uom): string => $this->normalizeCode($uom->code))->all();

        $conflicts = [];
        $vehiclePlans = [];
        $jobPlans = [];
        $matchedVehicleCount = 0;
        $missingVehicleCount = 0;
        $lineCount = 0;

        $jobIds = array_map(static fn (array $job): string => (string) ($job['id'] ?? ''), $jobs);
        if (count($jobIds) !== count(array_unique($jobIds))) {
            $conflicts[] = 'The source contains duplicate vehicle service job IDs.';
        }
        $lineIds = array_map(static fn (array $line): string => (string) ($line['id'] ?? ''), $source['vehicle_service_job_lines']);
        if (count($lineIds) !== count(array_unique($lineIds))) {
            $conflicts[] = 'The source contains duplicate vehicle service job-line IDs.';
        }
        foreach ($source['vehicle_service_job_lines'] as $line) {
            if (! in_array((string) ($line['vehicle_service_job_id'] ?? ''), $jobIds, true)) {
                $conflicts[] = "Source job line {$line['id']} is orphaned.";
            }
        }

        foreach ($jobs as $job) {
            $this->validateJob($job, $linesByJob[(string) ($job['id'] ?? '')] ?? [], $conflicts);
            $sourceVehicleId = (string) ($job['vehicle_id'] ?? '');
            $sourceVehicle = $sourceVehicles[$sourceVehicleId] ?? null;
            if ($sourceVehicle === null) {
                $conflicts[] = "Job {$job['id']} references missing source vehicle {$sourceVehicleId}.";

                continue;
            }

            if (! isset($vehiclePlans[$sourceVehicleId])) {
                $vehiclePlans[$sourceVehicleId] = $this->vehiclePlan(
                    $sourceVehicle,
                    $ownershipsByVehicle[$sourceVehicleId] ?? [],
                    $sourceCustomers,
                    $vehicleIndexes,
                    $customerIndexes,
                    $conflicts,
                );
                if ($vehiclePlans[$sourceVehicleId]['target_id'] === null) {
                    $missingVehicleCount++;
                } else {
                    $matchedVehicleCount++;
                }
            }

            $jobLines = $linesByJob[(string) ($job['id'] ?? '')] ?? [];
            $lineCount += count($jobLines);
            $customer = $sourceCustomers[(string) ($job['customer_id'] ?? '')] ?? null;
            $customerResolution = $customer === null ? ['target_id' => null, 'conflict' => null] : $this->resolveCustomer($customer, $customerIndexes);
            if ($customerResolution['conflict'] !== null) {
                $conflicts[] = "Job {$job['id']} customer conflict: {$customerResolution['conflict']}";
            }

            $payloadHash = $this->payloadHash(['job' => $job, 'lines' => $jobLines]);
            $existing = VehicleServiceLegacyHistory::query()
                ->where('tenant_id', $tenantId)
                ->where('organization_scope_key', $scopeKey)
                ->where('source_system', self::SOURCE_SYSTEM)
                ->where('source_table', self::SOURCE_TABLE)
                ->where('source_record_id', (string) $job['id'])
                ->first();
            $skip = false;
            if ($existing !== null) {
                if (! hash_equals((string) $existing->source_payload_sha256, $payloadHash)) {
                    $conflicts[] = "Previously imported job {$job['id']} has changed in the supplied source.";
                } else {
                    $skip = true;
                }
            }

            $jobPlans[] = [
                'job' => $job,
                'lines' => $this->linePlans($jobLines, $sourceItems, $sourceUoms, $itemByCode, $uomByCode),
                'vehicle_source_id' => $sourceVehicleId,
                'customer_source_id' => $customer === null ? null : (string) $customer['id'],
                'customer_target_id' => $customerResolution['target_id'],
                'customer' => $customer,
                'payload_hash' => $payloadHash,
                'skip' => $skip,
            ];
        }

        return [
            'source_path' => $path,
            'source_filename' => basename($path),
            'source_sha256' => $sourceSha256,
            'scope_key' => $scopeKey,
            'already_imported' => $alreadyImported,
            'source_job_count' => count($jobs),
            'source_line_count' => $lineCount,
            'matched_vehicle_count' => $matchedVehicleCount,
            'missing_vehicle_count' => $missingVehicleCount,
            'conflicts' => array_values(array_unique($conflicts)),
            'vehicle_plans' => $vehiclePlans,
            'job_plans' => $jobPlans,
        ];
    }

    /** @param array<string, mixed> $analysis @return array<string, mixed> */
    private function apply(array $analysis, int $tenantId, ?int $organizationUnitId): array
    {
        $now = now();
        $batch = new VehicleServiceLegacyImportBatch;
        $batch->forceFill([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'organization_scope_key' => $analysis['scope_key'],
            'source_system' => self::SOURCE_SYSTEM,
            'source_filename' => $analysis['source_filename'],
            'source_sha256' => $analysis['source_sha256'],
            'status' => 'running',
            'source_job_count' => $analysis['source_job_count'],
            'conflict_count' => 0,
            'started_at' => $now,
        ])->save();

        $createdCustomers = [];
        $createdVehicles = [];
        foreach ($analysis['vehicle_plans'] as $sourceVehicleId => $plan) {
            if ($plan['target_id'] !== null) {
                continue;
            }

            $customerId = $plan['owner_customer_target_id'];
            $sourceCustomerId = (string) $plan['owner_customer']['id'];
            if ($customerId === null) {
                $customer = $this->createCustomer($plan['owner_customer'], $tenantId, $organizationUnitId, $analysis['source_sha256']);
                $customerId = (int) $customer->getKey();
                $createdCustomers[$sourceCustomerId] = $customerId;
            }

            $vehicle = $this->createVehicle(
                $plan['source'],
                $plan['ownership'],
                $customerId,
                $tenantId,
                $organizationUnitId,
                $analysis['source_sha256'],
            );
            $createdVehicles[$sourceVehicleId] = (int) $vehicle->getKey();
        }

        $importedJobs = 0;
        $importedLines = 0;
        foreach ($analysis['job_plans'] as $plan) {
            if ($plan['skip']) {
                continue;
            }

            $job = $plan['job'];
            $sourceVehicleId = $plan['vehicle_source_id'];
            $vehicleId = $analysis['vehicle_plans'][$sourceVehicleId]['target_id'] ?? $createdVehicles[$sourceVehicleId] ?? null;
            if ($vehicleId === null) {
                throw new RuntimeException("No target vehicle was resolved for source vehicle {$sourceVehicleId}.");
            }
            $customerId = $plan['customer_target_id'];
            if ($customerId === null && $plan['customer_source_id'] !== null) {
                $customerId = $createdCustomers[$plan['customer_source_id']] ?? null;
            }

            $jobTypeRaw = (string) ($job['type'] ?? '');
            $history = new VehicleServiceLegacyHistory;
            $history->forceFill([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'organization_scope_key' => $analysis['scope_key'],
                'import_batch_id' => $batch->getKey(),
                'vehicle_id' => $vehicleId,
                'customer_id' => $customerId,
                'source_system' => self::SOURCE_SYSTEM,
                'source_table' => self::SOURCE_TABLE,
                'source_record_id' => (string) $job['id'],
                'source_payload_sha256' => $plan['payload_hash'],
                'legacy_job_number' => (string) ($job['job_number'] ?? 'Legacy job '.$job['id']),
                'service_date' => $job['job_date'],
                'job_type' => VehicleServiceJobType::tryFrom($jobTypeRaw)?->value,
                'job_type_raw' => $jobTypeRaw === '' ? 'not_recorded' : $jobTypeRaw,
                'status_raw' => $job['status'] ?? null,
                'odometer_reading' => $this->nullableDecimal($job['odometer_reading'] ?? null),
                'odometer_unit' => $analysis['vehicle_plans'][$sourceVehicleId]['source']['odometer_unit'] ?? null,
                'next_service_mileage' => $this->nullableDecimal($job['next_service_mileage'] ?? null),
                'vehicle_registration_snapshot' => $analysis['vehicle_plans'][$sourceVehicleId]['source']['registration_number'] ?? null,
                'customer_code_snapshot' => $plan['customer']['code'] ?? $plan['customer']['customer_number'] ?? null,
                'customer_name_snapshot' => $plan['customer']['display_name'] ?? $plan['customer']['name'] ?? null,
                'source_payload' => $job,
                'imported_at' => $now,
            ])->save();

            foreach ($plan['lines'] as $linePlan) {
                $line = $linePlan['source'];
                $item = new VehicleServiceLegacyHistoryItem;
                $item->forceFill([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'legacy_history_id' => $history->getKey(),
                    'source_record_id' => (string) $line['id'],
                    'source_payload_sha256' => $this->payloadHash($line),
                    'line_number' => (int) ($line['line_number'] ?? 0),
                    'category' => $this->lineCategory($line['line_source_type'] ?? null),
                    'source_type_raw' => $line['line_source_type'] ?? null,
                    'item_id' => $linePlan['item_id'],
                    'uom_id' => $linePlan['uom_id'],
                    'item_code_snapshot' => $linePlan['source_item']['code'] ?? null,
                    'item_name_snapshot' => $linePlan['source_item']['name'] ?? null,
                    'description' => (string) ($line['description'] ?: $linePlan['source_item']['name'] ?? 'Service item'),
                    'quantity' => $this->nullableDecimal($line['quantity'] ?? null) ?? '0.000000',
                    'uom_snapshot' => $linePlan['source_uom']['code'] ?? $linePlan['source_uom']['name'] ?? null,
                    'source_payload' => $line,
                    'imported_at' => $now,
                ])->save();
                $importedLines++;
            }
            $importedJobs++;
        }

        $summary = $this->publicResult($analysis, 'applied');
        $batch->forceFill([
            'status' => 'completed',
            'imported_job_count' => $importedJobs,
            'imported_line_count' => $importedLines,
            'created_customer_count' => count($createdCustomers),
            'created_vehicle_count' => count($createdVehicles),
            'summary' => $summary,
            'completed_at' => now(),
        ])->save();

        return [...$summary, 'batch_id' => (int) $batch->getKey(), 'imported_job_count' => $importedJobs, 'imported_line_count' => $importedLines, 'created_customer_count' => count($createdCustomers), 'created_vehicle_count' => count($createdVehicles)];
    }

    /**
     * @param  array<string, string|null>  $sourceVehicle
     * @param  list<array<string, string|null>>  $ownerships
     * @param  array<string, array<string, string|null>>  $sourceCustomers
     * @param  array<string, array<string, list<Vehicle>>>  $vehicleIndexes
     * @param  array<string, array<string, list<Customer>>>  $customerIndexes
     * @param  list<string>  $conflicts
     * @return array<string, mixed>
     */
    private function vehiclePlan(array $sourceVehicle, array $ownerships, array $sourceCustomers, array $vehicleIndexes, array $customerIndexes, array &$conflicts): array
    {
        $resolution = $this->resolveVehicle($sourceVehicle, $vehicleIndexes);
        if ($resolution['conflict'] !== null) {
            $conflicts[] = "Vehicle {$sourceVehicle['id']} conflict: {$resolution['conflict']}";
        }
        if ($resolution['target'] instanceof Vehicle && $resolution['target']->trashed()) {
            $conflicts[] = "Vehicle {$sourceVehicle['id']} matches a deleted current-system vehicle.";
        }

        $ownership = null;
        $ownerCustomer = null;
        $ownerCustomerTargetId = null;
        if ($resolution['target'] === null) {
            $currentOwnerships = array_values(array_filter($ownerships, static fn (array $row): bool => ($row['owner_type'] ?? null) === VehicleOwnerType::Customer->value && ($row['is_current'] ?? '0') === '1' && ($row['ended_at'] ?? null) === null));
            if (count($currentOwnerships) !== 1) {
                $conflicts[] = "Missing vehicle {$sourceVehicle['id']} does not have exactly one valid current customer owner.";
            } else {
                $ownership = $currentOwnerships[0];
                $ownerCustomer = $sourceCustomers[(string) ($ownership['owner_id'] ?? '')] ?? null;
                if ($ownerCustomer === null) {
                    $conflicts[] = "Missing vehicle {$sourceVehicle['id']} references an unavailable current owner.";
                } else {
                    $ownerResolution = $this->resolveCustomer($ownerCustomer, $customerIndexes);
                    $ownerCustomerTargetId = $ownerResolution['target_id'];
                    if ($ownerResolution['conflict'] !== null) {
                        $conflicts[] = "Missing vehicle {$sourceVehicle['id']} owner conflict: {$ownerResolution['conflict']}";
                    }
                }
            }
            $this->validateMissingVehicle($sourceVehicle, $ownership, $ownerCustomer, $conflicts);
        }

        return [
            'source' => $sourceVehicle,
            'target_id' => $resolution['target'] === null ? null : (int) $resolution['target']->getKey(),
            'matched_by' => $resolution['matched_by'],
            'ownership' => $ownership,
            'owner_customer' => $ownerCustomer,
            'owner_customer_target_id' => $ownerCustomerTargetId,
        ];
    }

    /** @param array<string, string|null> $source @param array<string, array<string, list<Vehicle>>> $indexes @return array{target: Vehicle|null, matched_by: string|null, conflict: string|null} */
    private function resolveVehicle(array $source, array $indexes): array
    {
        $identifiers = [
            'vin_number' => $this->normalizeIdentifier($source['vin_number'] ?? null),
            'chassis_number' => $this->normalizeIdentifier($source['chassis_number'] ?? null),
            'registration_number' => $this->normalizeIdentifier($source['registration_number'] ?? null),
            'engine_number' => $this->normalizeIdentifier($source['engine_number'] ?? null),
            'code' => $this->normalizeCode($source['code'] ?? null),
            'vehicle_number' => $this->normalizeCode($source['vehicle_number'] ?? null),
        ];
        $matches = [];
        foreach ($identifiers as $field => $value) {
            if ($value === '') {
                continue;
            }
            $candidates = $indexes[$field][$value] ?? [];
            if (count($candidates) > 1) {
                return ['target' => null, 'matched_by' => null, 'conflict' => "{$field} is ambiguous after normalization."];
            }
            if ($candidates !== []) {
                $matches[$field] = $candidates[0];
            }
        }
        if ($matches === []) {
            return ['target' => null, 'matched_by' => null, 'conflict' => null];
        }
        $targetIds = array_unique(array_map(static fn (Vehicle $vehicle): int => (int) $vehicle->getKey(), $matches));
        if (count($targetIds) !== 1) {
            return ['target' => null, 'matched_by' => null, 'conflict' => 'different identifiers point to different current-system vehicles.'];
        }

        $matchedBy = array_key_first($matches);
        $target = $matches[$matchedBy];
        if (in_array($matchedBy, ['code', 'vehicle_number'], true) && $identifiers['registration_number'] !== '') {
            $targetRegistration = $this->normalizeIdentifier($target->registration_number);
            if ($targetRegistration !== '' && $targetRegistration !== $identifiers['registration_number']) {
                return ['target' => null, 'matched_by' => null, 'conflict' => 'legacy code matches but registration numbers disagree.'];
            }
        }

        return ['target' => $target, 'matched_by' => $matchedBy, 'conflict' => null];
    }

    /** @param array<string, string|null> $source @param array<string, array<string, list<Customer>>> $indexes @return array{target_id: int|null, conflict: string|null} */
    private function resolveCustomer(array $source, array $indexes): array
    {
        $matches = [];
        foreach (['customer_number', 'code'] as $field) {
            $value = $this->normalizeCode($source[$field] ?? null);
            if ($value === '') {
                continue;
            }
            $candidates = $indexes[$field][$value] ?? [];
            if (count($candidates) > 1) {
                return ['target_id' => null, 'conflict' => "{$field} is ambiguous."];
            }
            if ($candidates !== []) {
                $matches[$field] = $candidates[0];
            }
        }
        if ($matches === []) {
            return ['target_id' => null, 'conflict' => null];
        }
        $targetIds = array_unique(array_map(static fn (Customer $customer): int => (int) $customer->getKey(), $matches));
        if (count($targetIds) !== 1) {
            return ['target_id' => null, 'conflict' => 'customer number and code point to different customers.'];
        }
        $target = reset($matches);
        if ($target->trashed()) {
            return ['target_id' => null, 'conflict' => 'the source customer matches a deleted current-system customer.'];
        }
        foreach (['customer_number', 'code'] as $field) {
            $sourceValue = $this->normalizeCode($source[$field] ?? null);
            $targetValue = $this->normalizeCode($target->getAttribute($field));
            if ($sourceValue !== '' && $targetValue !== '' && $sourceValue !== $targetValue) {
                return ['target_id' => null, 'conflict' => "matched customer has a different {$field}."];
            }
        }

        return ['target_id' => (int) $target->getKey(), 'conflict' => null];
    }

    /** @param array<string, string|null> $vehicle @param array<string, string|null>|null $ownership @param array<string, string|null>|null $customer @param list<string> $conflicts */
    private function validateMissingVehicle(array $vehicle, ?array $ownership, ?array $customer, array &$conflicts): void
    {
        if ($this->normalizeIdentifier($vehicle['registration_number'] ?? null) === ''
            && $this->normalizeIdentifier($vehicle['chassis_number'] ?? null) === ''
            && $this->normalizeIdentifier($vehicle['vin_number'] ?? null) === '') {
            $conflicts[] = "Missing vehicle {$vehicle['id']} has no reliable registration, chassis, or VIN identifier.";
        }
        if (($vehicle['manufacture_year'] ?? null) !== null) {
            $year = (int) $vehicle['manufacture_year'];
            if ($year < 1886 || $year > ((int) date('Y')) + 1) {
                $conflicts[] = "Missing vehicle {$vehicle['id']} has an invalid manufacture year.";
            }
        }
        if ($this->enumValue(VehicleStatus::class, $vehicle['status'] ?? null) === null) {
            $conflicts[] = "Missing vehicle {$vehicle['id']} has an unsupported status.";
        }
        if ($ownership !== null && $this->enumValue(VehicleOwnershipType::class, $ownership['ownership_type'] ?? null) === null) {
            $conflicts[] = "Missing vehicle {$vehicle['id']} has an unsupported ownership type.";
        }
        if ($customer !== null && ($this->enumValue(CustomerType::class, $customer['customer_type'] ?? null) === null || $this->enumValue(CustomerStatus::class, $customer['status'] ?? null) === null)) {
            $conflicts[] = "Missing vehicle {$vehicle['id']} has an owner with unsupported customer type or status.";
        }
        $this->validateDate($vehicle['registration_date'] ?? null, "Missing vehicle {$vehicle['id']} registration date", $conflicts);
        if ($ownership !== null) {
            $this->validateDateTime($ownership['started_at'] ?? null, "Missing vehicle {$vehicle['id']} ownership start", $conflicts);
        }
    }

    /** @param array<string, string|null> $job @param list<array<string, string|null>> $lines @param list<string> $conflicts */
    private function validateJob(array $job, array $lines, array &$conflicts): void
    {
        $jobId = (string) ($job['id'] ?? 'unknown');
        if (($job['job_date'] ?? null) === null) {
            $conflicts[] = "Source job {$jobId} has no service date.";
        } else {
            $this->validateDate($job['job_date'], "Source job {$jobId} service date", $conflicts);
        }
        foreach (['odometer_reading', 'next_service_mileage'] as $field) {
            $value = $job[$field] ?? null;
            if ($value !== null && (! is_numeric($value) || (float) $value < 0)) {
                $conflicts[] = "Source job {$jobId} has an invalid {$field}.";
            }
        }
        foreach ($lines as $line) {
            $quantity = $line['quantity'] ?? null;
            if ($quantity === null || ! is_numeric($quantity) || (float) $quantity < 0) {
                $conflicts[] = "Source job line {$line['id']} has an invalid quantity.";
            }
        }
    }

    /** @param list<array<string, string|null>> $lines @param array<string, array<string, string|null>> $sourceItems @param array<string, array<string, string|null>> $sourceUoms @param array<string, Item> $itemByCode @param array<string, UnitOfMeasureModel> $uomByCode @return list<array<string, mixed>> */
    private function linePlans(array $lines, array $sourceItems, array $sourceUoms, array $itemByCode, array $uomByCode): array
    {
        return array_map(function (array $line) use ($sourceItems, $sourceUoms, $itemByCode, $uomByCode): array {
            $sourceItem = $sourceItems[(string) ($line['item_id'] ?? '')] ?? null;
            $sourceUom = $sourceUoms[(string) ($line['uom_id'] ?? '')] ?? null;
            $item = $sourceItem === null ? null : ($itemByCode[$this->normalizeCode($sourceItem['code'] ?? null)] ?? null);
            $uom = $sourceUom === null ? null : ($uomByCode[$this->normalizeCode($sourceUom['code'] ?? null)] ?? null);

            return [
                'source' => $line,
                'source_item' => $sourceItem,
                'source_uom' => $sourceUom,
                'item_id' => $item instanceof Item && ! $item->trashed() ? (int) $item->getKey() : null,
                'uom_id' => $uom instanceof UnitOfMeasureModel && ! $uom->trashed() ? (int) $uom->getKey() : null,
            ];
        }, $lines);
    }

    /** @param array<string, string|null> $source */
    private function createCustomer(array $source, int $tenantId, ?int $organizationUnitId, string $sourceSha256): Customer
    {
        $type = CustomerType::tryFrom((string) $source['customer_type']);
        $status = CustomerStatus::tryFrom((string) $source['status']);
        if ($type === null || $status === null) {
            throw new InvalidArgumentException('The source owner customer type or status is unsupported.');
        }

        return $this->customers->create(new CreateCustomerData(
            tenantId: $tenantId,
            code: $this->nullableText($source['code'] ?? null),
            name: (string) ($source['name'] ?? $source['display_name'] ?? 'Legacy vehicle owner'),
            customerType: $type,
            organizationUnitId: $organizationUnitId,
            customerNumber: $this->nullableText($source['customer_number'] ?? null),
            legalName: $this->nullableText($source['legal_name'] ?? null),
            displayName: $this->nullableText($source['display_name'] ?? null),
            status: $status,
            email: $this->nullableText($source['email'] ?? null),
            phone: $this->nullableText($source['phone'] ?? null),
            mobile: $this->nullableText($source['mobile'] ?? null),
            notes: 'Imported as the verified current owner of a legacy-history vehicle.',
            metadata: ['legacy_source' => self::SOURCE_SYSTEM, 'legacy_record_id' => $source['id'], 'source_sha256' => $sourceSha256],
        ));
    }

    /** @param array<string, string|null> $source @param array<string, string|null> $ownership */
    private function createVehicle(array $source, array $ownership, int $customerId, int $tenantId, ?int $organizationUnitId, string $sourceSha256): Vehicle
    {
        $status = VehicleStatus::tryFrom((string) $source['status']);
        $ownershipType = VehicleOwnershipType::tryFrom((string) $ownership['ownership_type']);
        if ($status === null || $ownershipType === null) {
            throw new InvalidArgumentException('The source vehicle status or ownership type is unsupported.');
        }

        return $this->vehicles->create(new CreateVehicleData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            code: $this->nullableText($source['code'] ?? null),
            registrationNumber: $this->nullableText($source['registration_number'] ?? null),
            chassisNumber: $this->nullableText($source['chassis_number'] ?? null),
            engineNumber: $this->nullableText($source['engine_number'] ?? null),
            vinNumber: $this->nullableText($source['vin_number'] ?? null),
            manufactureYear: ($source['manufacture_year'] ?? null) === null ? null : (int) $source['manufacture_year'],
            registrationDate: $this->nullableText($source['registration_date'] ?? null),
            color: $this->nullableText($source['color'] ?? null),
            fuelType: VehicleFuelType::tryFrom((string) ($source['fuel_type'] ?? '')),
            transmissionType: VehicleTransmissionType::tryFrom((string) ($source['transmission_type'] ?? '')),
            odometerReading: $this->nullableDecimal($source['odometer_reading'] ?? null) ?? '0.000000',
            odometerUnit: $this->nullableText($source['odometer_unit'] ?? null),
            fuelLevel: $this->nullableText($source['fuel_level'] ?? null),
            status: $status,
            notes: 'Imported because this vehicle is referenced by legacy service history.',
            metadata: ['legacy_source' => self::SOURCE_SYSTEM, 'legacy_record_id' => $source['id'], 'legacy_vehicle_number' => $source['vehicle_number'] ?? null, 'source_sha256' => $sourceSha256],
            ownerships: [new VehicleOwnershipDraftData(
                ownerType: VehicleOwnerType::Customer,
                ownerId: $customerId,
                ownershipType: $ownershipType,
                startedAt: (string) $ownership['started_at'],
                endedAt: null,
                isCurrent: true,
                notes: 'Imported current ownership from verified legacy data.',
            )],
        ));
    }

    /** @param list<Vehicle> $vehicles @return array<string, array<string, list<Vehicle>>> */
    private function vehicleIndexes(array $vehicles): array
    {
        $indexes = array_fill_keys(['vin_number', 'chassis_number', 'registration_number', 'engine_number', 'code', 'vehicle_number'], []);
        foreach ($vehicles as $vehicle) {
            foreach (array_keys($indexes) as $field) {
                $value = in_array($field, ['code', 'vehicle_number'], true)
                    ? $this->normalizeCode($vehicle->getAttribute($field))
                    : $this->normalizeIdentifier($vehicle->getAttribute($field));
                if ($value !== '') {
                    $indexes[$field][$value][] = $vehicle;
                }
            }
        }

        return $indexes;
    }

    /** @param list<Customer> $customers @return array<string, array<string, list<Customer>>> */
    private function customerIndexes(array $customers): array
    {
        $indexes = ['customer_number' => [], 'code' => []];
        foreach ($customers as $customer) {
            foreach (array_keys($indexes) as $field) {
                $value = $this->normalizeCode($customer->getAttribute($field));
                if ($value !== '') {
                    $indexes[$field][$value][] = $customer;
                }
            }
        }

        return $indexes;
    }

    /** @param list<array<string, string|null>> $rows @return array<string, array<string, string|null>> */
    private function keyById(array $rows): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            if (($row['id'] ?? null) !== null) {
                $indexed[(string) $row['id']] = $row;
            }
        }

        return $indexed;
    }

    /** @param list<array<string, string|null>> $rows @return array<string, list<array<string, string|null>>> */
    private function groupBy(array $rows, string $column): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(string) ($row[$column] ?? '')][] = $row;
        }

        return $grouped;
    }

    /** @param array<string, mixed> $analysis @return array<string, mixed> */
    private function publicResult(array $analysis, string $mode): array
    {
        return [
            'mode' => $mode,
            'source_filename' => $analysis['source_filename'],
            'source_sha256' => $analysis['source_sha256'],
            'source_job_count' => $analysis['source_job_count'],
            'source_line_count' => $analysis['source_line_count'],
            'matched_vehicle_count' => $analysis['matched_vehicle_count'],
            'missing_vehicle_count' => $analysis['missing_vehicle_count'],
            'skipped_existing_job_count' => count(array_filter($analysis['job_plans'], static fn (array $plan): bool => $plan['skip'])),
            'conflict_count' => count($analysis['conflicts']),
            'conflicts' => $analysis['conflicts'],
            'writes_performed' => $mode === 'applied',
        ];
    }

    /** @param array<string, mixed> $payload */
    private function payloadHash(array $payload): string
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash('sha256', $json);
    }

    private function normalizeIdentifier(mixed $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper(trim((string) $value))) ?? '';
    }

    private function normalizeCode(mixed $value): string
    {
        return mb_strtoupper(trim((string) $value));
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableDecimal(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (! preg_match('/^\+?(\d{1,14})(?:\.(\d{1,6}))?$/', $value, $matches)) {
            throw new InvalidArgumentException('Legacy mileage or quantity values must be non-negative numbers.');
        }

        $whole = ltrim($matches[1], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($matches[2] ?? '', 6, '0');

        return $whole.'.'.$fraction;
    }

    private function lineCategory(?string $sourceType): string
    {
        return in_array($sourceType, ['inventory_item', 'external_item'], true) ? 'part' : 'work';
    }

    /** @param class-string<\BackedEnum> $enum */
    private function enumValue(string $enum, ?string $value): ?string
    {
        return $value === null ? null : $enum::tryFrom($value)?->value;
    }

    /** @param list<string> $conflicts */
    private function validateDate(?string $value, string $label, array &$conflicts): void
    {
        if ($value === null || $value === '') {
            return;
        }
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (Throwable) {
            $date = false;
        }
        if ($date === false || $date->format('Y-m-d') !== $value) {
            $conflicts[] = "{$label} is invalid.";
        }
    }

    /** @param list<string> $conflicts */
    private function validateDateTime(?string $value, string $label, array &$conflicts): void
    {
        if ($value === null || $value === '') {
            $conflicts[] = "{$label} is missing.";

            return;
        }
        try {
            CarbonImmutable::parse($value);
        } catch (Throwable) {
            $conflicts[] = "{$label} is invalid.";
        }
    }

    private function scopeKey(?int $organizationUnitId): string
    {
        return $organizationUnitId === null ? 'global' : 'organization:'.$organizationUnitId;
    }

    private function lockKey(int $tenantId, ?int $organizationUnitId): string
    {
        return 'vehicle-service:legacy-history-import:'.$tenantId.':'.$this->scopeKey($organizationUnitId);
    }
}
