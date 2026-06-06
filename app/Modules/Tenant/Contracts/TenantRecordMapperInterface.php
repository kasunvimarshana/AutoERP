<?php

declare(strict_types=1);

namespace Modules\Tenant\Contracts;

use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\DTOs\TenantValueData;
use Modules\Tenant\Entities\Tenant;

interface TenantRecordMapperInterface
{
    public function toValueData(DataRecord $record): TenantValueData;

    public function toEntity(DataRecord $record): Tenant;
}
