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

use Modules\Hr\DTOs\HrDesignationData;
use Modules\Hr\Http\Resources\HrDesignationResource;
use Modules\Hr\Services\HrDesignationService;

final class HrDesignationController extends HrMasterController
{
    public function __construct(HrDesignationService $service)
    {
        parent::__construct($service, HrDesignationData::class, HrDesignationResource::class);
    }
}
