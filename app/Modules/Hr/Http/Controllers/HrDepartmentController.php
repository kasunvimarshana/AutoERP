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
use Modules\Hr\DTOs\HrDepartmentData;
use Modules\Hr\Http\Resources\HrDepartmentResource;
use Modules\Hr\Services\HrDepartmentService;
final class HrDepartmentController extends HrMasterController { public function __construct(HrDepartmentService $service) { parent::__construct($service, HrDepartmentData::class, HrDepartmentResource::class); } }
