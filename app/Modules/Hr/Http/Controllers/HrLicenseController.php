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
use Modules\Hr\DTOs\HrLicenseData;
use Modules\Hr\Http\Resources\HrLicenseResource;
use Modules\Hr\Services\HrLicenseService;
final class HrLicenseController extends HrMasterController { public function __construct(HrLicenseService $service) { parent::__construct($service, HrLicenseData::class, HrLicenseResource::class); } }
