<?php
declare(strict_types=1);
namespace Modules\Hr\Http\Controllers;
use Modules\Hr\DTOs\HrDepartmentData;
use Modules\Hr\Http\Resources\HrDepartmentResource;
use Modules\Hr\Services\HrDepartmentService;
final class HrDepartmentController extends HrMasterController { public function __construct(HrDepartmentService $service) { parent::__construct($service, HrDepartmentData::class, HrDepartmentResource::class); } }
