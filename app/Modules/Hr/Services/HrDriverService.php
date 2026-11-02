<?php

declare(strict_types=1);

namespace Modules\Hr\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Hr\Enums\EmployeeAvailabilityStatus;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Models\HrDesignation;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeLicenseAssignment;
use Modules\Hr\Models\HrEmployeeStatusHistory;
use Modules\Hr\Models\HrLicense;

final class HrDriverService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly EmployeeNumberService $numbers,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $tenantId, ?int $organizationUnitId, int $perPage = 25): LengthAwarePaginator
    {
        $query = $this->driverQuery($tenantId, $organizationUnitId);

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('metadata->nic_passport', 'like', "%{$search}%")
                    ->orWhere('metadata->driving_license_number', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['availability_status'])) {
            $query->where('availability_status', $filters['availability_status']);
        }

        $query->with(['designation', 'department', 'licenseAssignments.license']);
        $query->orderByDesc('id');

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($perPage);

        $this->enrichWithRentalAssignments($paginator->items(), $tenantId);

        return $paginator;
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): HrEmployee
    {
        /** @var HrEmployee $driver */
        $driver = $this->driverQuery($tenantId, $organizationUnitId)
            ->with(['designation', 'department', 'employmentType', 'licenseAssignments.license', 'statusHistories'])
            ->findOrFail($id);

        $this->enrichWithRentalAssignments([$driver], $tenantId, true);

        return $driver;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $tenantId, ?int $organizationUnitId, array $data, ?int $userId): HrEmployee
    {
        return DB::transaction(function () use ($tenantId, $organizationUnitId, $data, $userId): HrEmployee {
            $designation = $this->resolveDriverDesignation($tenantId, $organizationUnitId);
            $employeeNumber = ! empty($data['employee_number'])
                ? (string) $data['employee_number']
                : 'DRV-'.$this->nextDriverNumber($tenantId);

            $firstName = (string) $data['first_name'];
            $lastName = (string) ($data['last_name'] ?? '');
            $displayName = ! empty($data['display_name'])
                ? (string) $data['display_name']
                : trim("{$firstName} {$lastName}");

            $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
            $metadata['is_driver'] = true;
            $metadata['nic_passport'] = $data['nic_passport'] ?? $metadata['nic_passport'] ?? null;
            $metadata['driving_license_number'] = $data['driving_license_number'] ?? $metadata['driving_license_number'] ?? null;
            $metadata['driving_license_classes'] = $data['driving_license_classes'] ?? $metadata['driving_license_classes'] ?? [];
            $metadata['driving_license_issued'] = $data['driving_license_issued'] ?? $metadata['driving_license_issued'] ?? null;
            $metadata['driving_license_expiry'] = $data['driving_license_expiry'] ?? $metadata['driving_license_expiry'] ?? null;
            $metadata['emergency_contact_name'] = $data['emergency_contact_name'] ?? $metadata['emergency_contact_name'] ?? null;
            $metadata['emergency_contact_phone'] = $data['emergency_contact_phone'] ?? $metadata['emergency_contact_phone'] ?? null;
            $metadata['emergency_contact_relationship'] = $data['emergency_contact_relationship'] ?? $metadata['emergency_contact_relationship'] ?? null;
            $metadata['address'] = $data['address'] ?? $metadata['address'] ?? null;
            $metadata['city'] = $data['city'] ?? $metadata['city'] ?? null;
            $metadata['blood_group'] = $data['blood_group'] ?? $metadata['blood_group'] ?? null;
            $metadata['work_start_time'] = $data['work_start_time'] ?? $metadata['work_start_time'] ?? null;
            $metadata['work_end_time'] = $data['work_end_time'] ?? $metadata['work_end_time'] ?? null;
            $metadata['standard_shift_hours'] = isset($data['standard_shift_hours']) && $data['standard_shift_hours'] !== ''
                ? (float) $data['standard_shift_hours']
                : ($metadata['standard_shift_hours'] ?? null);

            $status = $data['status'] ?? EmployeeStatus::Active->value;
            $availability = $data['availability_status'] ?? EmployeeAvailabilityStatus::Available->value;

            $driver = HrEmployee::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'employee_number' => $employeeNumber,
                'code' => $data['code'] ?? $employeeNumber,
                'first_name' => $firstName,
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $lastName,
                'display_name' => $displayName,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'mobile' => $data['mobile'] ?? ($data['phone'] ?? null),
                'designation_id' => $designation->getKey(),
                'joined_date' => $data['joined_date'] ?? now()->toDateString(),
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'status' => $status,
                'availability_status' => $availability,
                'default_hourly_rate' => $this->math->normalize($data['default_hourly_rate'] ?? '0'),
                'default_daily_rate' => $this->math->normalize($data['default_daily_rate'] ?? '0'),
                'notes' => $data['notes'] ?? null,
                'metadata' => $metadata,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            // Synchronize Driving License Assignment
            if (! empty($data['driving_license_number'])) {
                $license = $this->resolveDrivingLicense($tenantId, $organizationUnitId);
                HrEmployeeLicenseAssignment::query()->updateOrCreate(
                    [
                        'employee_id' => $driver->getKey(),
                        'license_id' => $license->getKey(),
                    ],
                    [
                        'tenant_id' => $tenantId,
                        'organization_unit_id' => $organizationUnitId,
                        'license_number' => (string) $data['driving_license_number'],
                        'issued_date' => $data['driving_license_issued'] ?? null,
                        'expiry_date' => $data['driving_license_expiry'] ?? null,
                        'status' => 'active',
                    ],
                );
            }

            return $driver->refresh()->load(['designation', 'licenseAssignments.license']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(HrEmployee $driver, array $data, ?int $userId): HrEmployee
    {
        return DB::transaction(function () use ($driver, $data): HrEmployee {
            $metadata = is_array($driver->metadata) ? $driver->metadata : [];
            $metadata['is_driver'] = true;

            foreach ([
                'nic_passport',
                'driving_license_number',
                'driving_license_classes',
                'driving_license_issued',
                'driving_license_expiry',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relationship',
                'address',
                'city',
                'blood_group',
                'work_start_time',
                'work_end_time',
                'standard_shift_hours',
            ] as $key) {
                if (array_key_exists($key, $data)) {
                    $metadata[$key] = ($key === 'standard_shift_hours' && $data[$key] !== null && $data[$key] !== '')
                        ? (float) $data[$key]
                        : $data[$key];
                }
            }

            $updateFields = ['metadata' => $metadata];

            if (isset($data['first_name'])) {
                $updateFields['first_name'] = (string) $data['first_name'];
            }
            if (array_key_exists('middle_name', $data)) {
                $updateFields['middle_name'] = $data['middle_name'];
            }
            if (array_key_exists('last_name', $data)) {
                $updateFields['last_name'] = $data['last_name'];
            }
            if (isset($data['display_name'])) {
                $updateFields['display_name'] = (string) $data['display_name'];
            } elseif (isset($data['first_name'])) {
                $ln = $data['last_name'] ?? $driver->last_name;
                $updateFields['display_name'] = trim("{$data['first_name']} {$ln}");
            }

            if (array_key_exists('code', $data) && $data['code'] !== null) {
                $updateFields['code'] = $data['code'];
            }
            if (array_key_exists('email', $data)) {
                $updateFields['email'] = $data['email'];
            }
            if (array_key_exists('phone', $data)) {
                $updateFields['phone'] = $data['phone'];
            }
            if (array_key_exists('mobile', $data)) {
                $updateFields['mobile'] = $data['mobile'];
            }
            if (array_key_exists('date_of_birth', $data)) {
                $updateFields['date_of_birth'] = $data['date_of_birth'];
            }
            if (array_key_exists('gender', $data)) {
                $updateFields['gender'] = $data['gender'];
            }
            if (array_key_exists('joined_date', $data)) {
                $updateFields['joined_date'] = $data['joined_date'];
            }
            if (array_key_exists('default_daily_rate', $data)) {
                $updateFields['default_daily_rate'] = $this->math->normalize($data['default_daily_rate']);
            }
            if (array_key_exists('default_hourly_rate', $data)) {
                $updateFields['default_hourly_rate'] = $this->math->normalize($data['default_hourly_rate']);
            }
            if (array_key_exists('status', $data)) {
                $updateFields['status'] = $data['status'];
            }
            if (array_key_exists('availability_status', $data)) {
                $updateFields['availability_status'] = $data['availability_status'];
            }
            if (array_key_exists('notes', $data)) {
                $updateFields['notes'] = $data['notes'];
            }

            unset($driver->current_assignment, $driver->recent_assignments);

            $driver->update($updateFields);

            // Sync license assignment if license details updated
            $licNo = $data['driving_license_number'] ?? $metadata['driving_license_number'] ?? null;
            if ($licNo !== null) {
                $license = $this->resolveDrivingLicense((int) $driver->tenant_id, $driver->organization_unit_id);
                HrEmployeeLicenseAssignment::query()->updateOrCreate(
                    [
                        'employee_id' => $driver->getKey(),
                        'license_id' => $license->getKey(),
                    ],
                    [
                        'tenant_id' => $driver->tenant_id,
                        'organization_unit_id' => $driver->organization_unit_id,
                        'license_number' => (string) $licNo,
                        'issued_date' => $data['driving_license_issued'] ?? $metadata['driving_license_issued'] ?? null,
                        'expiry_date' => $data['driving_license_expiry'] ?? $metadata['driving_license_expiry'] ?? null,
                        'status' => 'active',
                    ],
                );
            }

            return $driver->refresh()->load(['designation', 'licenseAssignments.license']);
        });
    }

    public function delete(HrEmployee $driver, int $tenantId): void
    {
        // Safety check: is the driver actively assigned to an ongoing vehicle rental allocation?
        if (Schema::hasTable('rental_driver_assignments')) {
            $today = CarbonImmutable::now()->startOfDay()->toDateString();
            $activeAssignmentsCount = DB::table('rental_driver_assignments')
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $driver->getKey())
                ->whereIn('status', ['active', 'planned'])
                ->where(function ($q) use ($today): void {
                    $q->whereNull('assigned_to')
                        ->orWhereDate('assigned_to', '>=', $today);
                })
                ->count();

            if ($activeAssignmentsCount > 0) {
                throw new InvalidArgumentException(
                    "Cannot delete driver '{$driver->display_name}' because they are currently assigned to an active vehicle rental allocation. Please unassign or complete the allocation first."
                );
            }
        }

        $driver->delete();
    }

    public function changeStatus(HrEmployee $driver, string $status, ?string $availabilityStatus, ?string $reason, ?int $userId): HrEmployee
    {
        $oldStatus = $driver->status instanceof EmployeeStatus ? $driver->status->value : (string) $driver->status;

        $updates = ['status' => $status];
        if ($availabilityStatus !== null) {
            $updates['availability_status'] = $availabilityStatus;
        }

        unset($driver->current_assignment, $driver->recent_assignments);

        $driver->update($updates);

        if ($userId !== null && Schema::hasTable('hr_employee_status_histories')) {
            HrEmployeeStatusHistory::query()->create([
                'tenant_id' => $driver->tenant_id,
                'organization_unit_id' => $driver->organization_unit_id,
                'employee_id' => $driver->getKey(),
                'old_status' => $oldStatus,
                'new_status' => $status,
                'reason' => $reason,
                'changed_by' => $userId,
                'changed_at' => now(),
            ]);
        }

        return $driver->refresh()->load(['designation', 'licenseAssignments.license']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function lookup(array $filters, int $tenantId, ?int $organizationUnitId, int $limit = 50): Collection
    {
        $query = $this->driverQuery($tenantId, $organizationUnitId)
            ->where('status', EmployeeStatus::Active->value);

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('metadata->driving_license_number', 'like', "%{$search}%");
            });
        }

        return $query->with('licenseAssignments')
            ->limit($limit)
            ->get()
            ->map(function (HrEmployee $driver): array {
                $meta = is_array($driver->metadata) ? $driver->metadata : [];
                $lic = $driver->licenseAssignments->first()?->license_number
                    ?? $meta['driving_license_number']
                    ?? null;

                return [
                    'id' => $driver->getKey(),
                    'name' => $driver->display_name,
                    'code' => $driver->code ?? $driver->employee_number,
                    'employee_number' => $driver->employee_number,
                    'mobile' => $driver->mobile ?? $driver->phone,
                    'license_number' => $lic,
                    'availability_status' => $driver->availability_status instanceof EmployeeAvailabilityStatus
                        ? $driver->availability_status->value
                        : (string) $driver->availability_status,
                ];
            });
    }

    private function driverQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        return HrEmployee::query()
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $q): void {
                $q->whereHas('designation', function (Builder $dq): void {
                    $dq->where('code', 'DRIVER')
                        ->orWhere('name', 'like', '%Driver%');
                })
                ->orWhere('metadata->is_driver', true);

                if (Schema::hasTable('rental_driver_assignments')) {
                    $q->orWhereExists(function ($sub): void {
                        $sub->select(DB::raw(1))
                            ->from('rental_driver_assignments')
                            ->whereColumn('rental_driver_assignments.employee_id', 'hr_employees.id');
                    });
                }
            });
    }

    /**
     * @param  iterable<HrEmployee>  $drivers
     */
    private function enrichWithRentalAssignments(iterable $drivers, int $tenantId, bool $withHistory = false): void
    {
        if (! Schema::hasTable('rental_driver_assignments')) {
            return;
        }

        $driverIds = [];
        foreach ($drivers as $d) {
            $driverIds[] = (int) $d->getKey();
        }

        if (empty($driverIds)) {
            return;
        }

        $now = CarbonImmutable::now()->startOfDay()->toDateString();

        // 1. Load active current assignment
        $activeAssignments = DB::table('rental_driver_assignments')
            ->join('rental_vehicle_allocations', 'rental_driver_assignments.vehicle_allocation_id', '=', 'rental_vehicle_allocations.id')
            ->join('vehicles', 'rental_vehicle_allocations.vehicle_id', '=', 'vehicles.id')
            ->leftJoin('rental_agreements', 'rental_driver_assignments.agreement_id', '=', 'rental_agreements.id')
            ->where('rental_driver_assignments.tenant_id', $tenantId)
            ->whereIn('rental_driver_assignments.employee_id', $driverIds)
            ->whereIn('rental_driver_assignments.status', ['active', 'planned'])
            ->where(function ($q) use ($now): void {
                $q->whereNull('rental_driver_assignments.assigned_to')
                    ->orWhereDate('rental_driver_assignments.assigned_to', '>=', $now);
            })
            ->select([
                'rental_driver_assignments.id as assignment_id',
                'rental_driver_assignments.employee_id',
                'rental_driver_assignments.assignment_role',
                'rental_driver_assignments.assigned_from',
                'rental_driver_assignments.assigned_to',
                'rental_driver_assignments.status as assignment_status',
                'rental_vehicle_allocations.id as allocation_id',
                'rental_vehicle_allocations.allocation_number',
                'vehicles.id as vehicle_id',
                'vehicles.vehicle_number',
                'vehicles.registration_number',
                'rental_agreements.agreement_number',
            ])
            ->get()
            ->keyBy('employee_id');

        foreach ($drivers as $driver) {
            $assignment = $activeAssignments->get($driver->getKey());
            $driver->current_assignment = $assignment !== null ? [
                'allocation_id' => $assignment->allocation_id,
                'allocation_number' => $assignment->allocation_number,
                'vehicle_id' => $assignment->vehicle_id,
                'vehicle_number' => $assignment->registration_number ?: $assignment->vehicle_number,
                'agreement_number' => $assignment->agreement_number,
                'role' => $assignment->assignment_role,
                'assigned_from' => $assignment->assigned_from,
                'assigned_to' => $assignment->assigned_to,
            ] : null;

            if ($withHistory) {
                $history = DB::table('rental_driver_assignments')
                    ->join('rental_vehicle_allocations', 'rental_driver_assignments.vehicle_allocation_id', '=', 'rental_vehicle_allocations.id')
                    ->join('vehicles', 'rental_vehicle_allocations.vehicle_id', '=', 'vehicles.id')
                    ->leftJoin('rental_agreements', 'rental_driver_assignments.agreement_id', '=', 'rental_agreements.id')
                    ->where('rental_driver_assignments.tenant_id', $tenantId)
                    ->where('rental_driver_assignments.employee_id', $driver->getKey())
                    ->orderByDesc('rental_driver_assignments.assigned_from')
                    ->limit(20)
                    ->select([
                        'rental_driver_assignments.id',
                        'rental_driver_assignments.assignment_role',
                        'rental_driver_assignments.assigned_from',
                        'rental_driver_assignments.assigned_to',
                        'rental_driver_assignments.status',
                        'rental_vehicle_allocations.id as allocation_id',
                        'vehicles.vehicle_number',
                        'vehicles.registration_number',
                        'rental_agreements.agreement_number',
                    ])
                    ->get()
                    ->map(fn ($row): array => [
                        'id' => $row->id,
                        'allocation_id' => $row->allocation_id,
                        'vehicle_number' => $row->registration_number ?: $row->vehicle_number,
                        'agreement_number' => $row->agreement_number,
                        'role' => $row->assignment_role,
                        'assigned_from' => $row->assigned_from,
                        'assigned_to' => $row->assigned_to,
                        'status' => $row->status,
                    ])
                    ->all();

                $driver->recent_assignments = $history;
            }
        }
    }

    private function resolveDriverDesignation(int $tenantId, ?int $organizationUnitId): HrDesignation
    {
        /** @var HrDesignation */
        return HrDesignation::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'DRIVER'],
            [
                'organization_unit_id' => $organizationUnitId,
                'name' => 'Driver',
                'description' => 'Vehicle Chauffeur and Transport Driver',
                'is_active' => true,
            ],
        );
    }

    private function resolveDrivingLicense(int $tenantId, ?int $organizationUnitId): HrLicense
    {
        /** @var HrLicense */
        return HrLicense::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'code' => 'DRIVING-LICENSE'],
            [
                'organization_unit_id' => $organizationUnitId,
                'name' => 'Driving License',
                'description' => 'Motor Traffic Driving License',
                'is_active' => true,
            ],
        );
    }

    private function nextDriverNumber(int $tenantId): string
    {
        $count = HrEmployee::query()->where('tenant_id', $tenantId)->count() + 1;

        return str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}
