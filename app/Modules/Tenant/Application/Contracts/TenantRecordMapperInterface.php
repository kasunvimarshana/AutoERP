<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Tenant\Application\DTOs\TenantValueData;
use Modules\Tenant\Domain\Entities\Tenant;

interface TenantRecordMapperInterface
{
    public function toValueData(DataRecord $record): TenantValueData;

    public function toEntity(DataRecord $record): Tenant;
}
