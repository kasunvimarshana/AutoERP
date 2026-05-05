<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Driver\Domain\Entities\License;
use Modules\Driver\Domain\RepositoryInterfaces\LicenseRepositoryInterface;
use Modules\Driver\Infrastructure\Persistence\Eloquent\Models\LicenseModel;

class EloquentLicenseRepository implements LicenseRepositoryInterface
{
    public function create(License $license): void
    {
        LicenseModel::create([
            'id' => $license->id,
            'driver_id' => $license->driverId,
            'license_number' => $license->licenseNumber,
            'license_class' => $license->licenseClass,
            'issue_date' => $license->issueDate,
            'expiry_date' => $license->expiryDate,
            'country_code' => $license->countryCode,
            'status' => $license->status,
        ]);
    }

    public function findById(string $id): ?License
    {
        $model = LicenseModel::find($id);
        return $model ? $this->modelToEntity($model) : null;
    }

    public function findByNumber(string $licenseNumber): ?License
    {
        $model = LicenseModel::where('license_number', $licenseNumber)->first();
        return $model ? $this->modelToEntity($model) : null;
    }

    public function getByDriver(string $driverId): array
    {
        $models = LicenseModel::where('driver_id', $driverId)->get();
        return $models->map(fn ($model) => $this->modelToEntity($model))->toArray();
    }

    public function getExpiringLicenses(string $tenantId, int $daysThreshold = 30): array
    {
        $models = LicenseModel::whereHas('driver', fn ($q) => $q->byTenant($tenantId))
            ->expiring($daysThreshold)
            ->get();
        
        return $models->map(fn ($model) => $this->modelToEntity($model))->toArray();
    }

    public function getExpiredLicenses(string $tenantId): array
    {
        $models = LicenseModel::whereHas('driver', fn ($q) => $q->byTenant($tenantId))
            ->expired()
            ->get();
        
        return $models->map(fn ($model) => $this->modelToEntity($model))->toArray();
    }

    public function update(License $license): void
    {
        LicenseModel::findOrFail($license->id)->update([
            'license_number' => $license->licenseNumber,
            'license_class' => $license->licenseClass,
            'issue_date' => $license->issueDate,
            'expiry_date' => $license->expiryDate,
            'country_code' => $license->countryCode,
            'status' => $license->status,
        ]);
    }

    public function delete(string $id): void
    {
        LicenseModel::findOrFail($id)->delete();
    }

    private function modelToEntity(LicenseModel $model): License
    {
        return new License(
            id: $model->id,
            driverId: $model->driver_id,
            licenseNumber: $model->license_number,
            licenseClass: $model->license_class,
            issueDate: $model->issue_date,
            expiryDate: $model->expiry_date,
            countryCode: $model->country_code,
            status: $model->status,
        );
    }
}
