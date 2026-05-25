<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\BiometricDeviceRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\BiometricDeviceModel;

final class EloquentBiometricDeviceRepository extends EloquentRepository implements BiometricDeviceRepositoryInterface
{
    public function __construct(BiometricDeviceModel $model)
    {
        parent::__construct($model);
    }
}