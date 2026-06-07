<?php
declare(strict_types=1);
namespace Modules\Hr\Services;
use Modules\Hr\Models\HrSkill;
final class HrSkillService extends HrMasterService { protected string $modelClass = HrSkill::class; protected string $label = 'HR skill'; }
