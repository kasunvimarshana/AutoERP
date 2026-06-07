<?php
declare(strict_types=1);
namespace Modules\Hr\Http\Controllers;
use Modules\Hr\DTOs\HrDesignationData;
use Modules\Hr\Http\Resources\HrDesignationResource;
use Modules\Hr\Services\HrDesignationService;
final class HrDesignationController extends HrMasterController { public function __construct(HrDesignationService $service) { parent::__construct($service, HrDesignationData::class, HrDesignationResource::class); } }
