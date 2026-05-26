<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class TenantConfigurationModel extends CoreModel
{
    public const TABLE = 'tenant_configurations';

    public const COLUMN_ID = 'id';

    public const COLUMN_TENANT_ID = 'tenant_id';

    public const COLUMN_KEY = 'key';

    public const COLUMN_VALUE = 'value';

    public const COLUMN_VALUE_TYPE = 'value_type';

    public const COLUMN_SOURCE = 'source';

    public const COLUMN_DESCRIPTION = 'description';

    public const COLUMN_UPDATED_AT = 'updated_at';

    protected $table = self::TABLE;

    protected $fillable = [
        self::COLUMN_TENANT_ID,
        self::COLUMN_KEY,
        self::COLUMN_VALUE,
        self::COLUMN_VALUE_TYPE,
        self::COLUMN_SOURCE,
        self::COLUMN_DESCRIPTION,
    ];
}
