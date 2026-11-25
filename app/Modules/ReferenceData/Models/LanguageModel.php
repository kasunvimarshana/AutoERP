<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\ReferenceData\Models;

use Modules\Core\Models\CoreModel;

final class LanguageModel extends CoreModel
{
    protected $table = 'languages';

    protected $fillable = ['code', 'name', 'native_name', 'is_active'];

    protected function casts(): array
    {
        return ['row_version' => 'integer', 'is_active' => 'boolean'];
    }
}
