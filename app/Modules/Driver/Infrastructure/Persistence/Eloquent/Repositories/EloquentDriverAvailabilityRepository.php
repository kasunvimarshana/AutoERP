<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Driver\Domain\Entities\DriverAvailability;
use Modules\Driver\Domain\RepositoryInterfaces\DriverAvailabilityRepositoryInterface;
use Modules\Driver\Infrastructure\Persistence\Eloquent\Models\DriverAvailabilityModel;

class EloquentDriverAvailabilityRepository implements DriverAvailabilityRepositoryInterface
{
    public function create(DriverAvailability $availability): void
    {
        DriverAvailabilityModel::create([
            'id' => $availability->id,
            'driver_id' => $availability->driverId,
            'start_date' => $availability->startDate,
            'end_date' => $availability->endDate,
            'is_available' => $availability->isAvailable,
        ]);
    }

    public function findById(string $id): ?DriverAvailability
    {
        $model = DriverAvailabilityModel::find($id);
        return $model ? $this->modelToEntity($model) : null;
    }

    public function getByDriver(string $driverId, int $days = 30): array
    {
        $models = DriverAvailabilityModel::where('driver_id', $driverId)
            ->whereBetween('start_date', [now(), now()->addDays($days)])
            ->orWhereBetween('end_date', [now(), now()->addDays($days)])
            ->get();
        
        return $models->map(fn ($model) => $this->modelToEntity($model))->toArray();
    }

    public function checkAvailability(string $driverId, \DateTime $date, \DateTime $from, \DateTime $until): bool
    {
        return DriverAvailabilityModel::where('driver_id', $driverId)
            ->where('is_available', true)
            ->whereDate('start_date', '<=', $date->format('Y-m-d'))
            ->whereDate('end_date', '>=', $date->format('Y-m-d'))
            ->exists();
    }

    public function update(DriverAvailability $availability): void
    {
        DriverAvailabilityModel::findOrFail($availability->id)->update([
            'start_date' => $availability->startDate,
            'end_date' => $availability->endDate,
            'is_available' => $availability->isAvailable,
        ]);
    }

    public function delete(string $id): void
    {
        DriverAvailabilityModel::findOrFail($id)->delete();
    }

    private function modelToEntity(DriverAvailabilityModel $model): DriverAvailability
    {
        return new DriverAvailability(
            id: $model->id,
            driverId: $model->driver_id,
            startDate: $model->start_date,
            endDate: $model->end_date,
            isAvailable: $model->is_available,
        );
    }
}
