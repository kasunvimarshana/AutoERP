<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Services;

use Modules\Hr\Models\HrCertification;

final class HrCertificationService extends HrMasterService
{
    protected string $modelClass = HrCertification::class;

    protected string $label = 'HR certification';
}
