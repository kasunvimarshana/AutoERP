<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Http\Controllers;

use Modules\Hr\DTOs\HrCertificationData;
use Modules\Hr\Http\Resources\HrCertificationResource;
use Modules\Hr\Services\HrCertificationService;

final class HrCertificationController extends HrMasterController
{
    public function __construct(HrCertificationService $service)
    {
        parent::__construct($service, HrCertificationData::class, HrCertificationResource::class);
    }
}
