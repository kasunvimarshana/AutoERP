<?php
declare(strict_types=1);
namespace Modules\Hr\Http\Controllers;
use Modules\Hr\DTOs\HrLicenseData;
use Modules\Hr\Http\Resources\HrLicenseResource;
use Modules\Hr\Services\HrLicenseService;
final class HrLicenseController extends HrMasterController { public function __construct(HrLicenseService $service) { parent::__construct($service, HrLicenseData::class, HrLicenseResource::class); } }
