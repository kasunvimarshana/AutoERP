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
use Modules\Hr\DTOs\HrSkillData;
use Modules\Hr\Http\Resources\HrSkillResource;
use Modules\Hr\Services\HrSkillService;
final class HrSkillController extends HrMasterController { public function __construct(HrSkillService $service) { parent::__construct($service, HrSkillData::class, HrSkillResource::class); } }
