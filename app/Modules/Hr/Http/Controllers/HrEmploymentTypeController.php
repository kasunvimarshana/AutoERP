<?php
declare(strict_types=1);
namespace Modules\Hr\Http\Controllers;
use Modules\Hr\DTOs\HrEmploymentTypeData;
use Modules\Hr\Http\Resources\HrEmploymentTypeResource;
use Modules\Hr\Services\HrEmploymentTypeService;
final class HrEmploymentTypeController extends HrMasterController { public function __construct(HrEmploymentTypeService $service) { parent::__construct($service, HrEmploymentTypeData::class, HrEmploymentTypeResource::class); } }
