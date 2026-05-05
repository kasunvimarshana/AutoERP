<?php

declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Persistence\Eloquent\Repositories;

use Carbon\Carbon;
use Modules\Driver\Domain\Entities\DriverLicense;
use Modules\Driver\Domain\RepositoryInterfaces\DriverLicenseRepositoryInterface;
use Modules\Driver\Infrastructure\Persistence\Eloquent\Models\DriverLicenseModel;

class EloquentDriverLicenseRepository implements DriverLicenseRepositoryInterface
{
    public function findById(int $id): ?DriverLicense
    {
        $model = DriverLicenseModel::find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByDriver(int $driverId): array
    {
        return DriverLicenseModel::where('driver_id', $driverId)
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function findExpiringSoon(int $tenantId, int $daysAhead = 30): array
    {
        $cutoff = Carbon::today()->addDays($daysAhead)->toDateString();

        return DriverLicenseModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $cutoff)
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function save(DriverLicense $license): DriverLicense
    {
        $data = [
            'tenant_id'      => $license->tenantId,
            'driver_id'      => $license->driverId,
            'license_number' => $license->licenseNumber,
            'license_class'  => $license->licenseClass,
            'issued_country' => $license->issuedCountry,
            'issue_date'     => $license->issueDate,
            'expiry_date'    => $license->expiryDate,
            'file_path'      => $license->filePath,
            'is_active'      => $license->isActive,
        ];

        if ($license->id === null) {
            $model = DriverLicenseModel::create($data);
        } else {
            $model = DriverLicenseModel::findOrFail($license->id);
            $model->update($data);
            $model->refresh();
        }

        return $this->toEntity($model);
    }

    public function delete(int $id): void
    {
        DriverLicenseModel::where('id', $id)->delete();
    }

    private function toEntity(DriverLicenseModel $model): DriverLicense
    {
        return new DriverLicense(
            id: $model->id,
            tenantId: (int) $model->tenant_id,
            driverId: (int) $model->driver_id,
            licenseNumber: $model->license_number,
            licenseClass: $model->license_class,
            issuedCountry: $model->issued_country,
            issueDate: $model->issue_date?->toDateString(),
            expiryDate: $model->expiry_date?->toDateString(),
            filePath: $model->file_path,
            isActive: (bool) $model->is_active,
        );
    }
}
