<?php
declare(strict_types=1);
namespace Modules\Hr\Services;
use Modules\Hr\Models\HrEmploymentType;
final class HrEmploymentTypeService extends HrMasterService { protected string $modelClass = HrEmploymentType::class; protected string $label = 'HR employment type'; protected bool $hasSortOrder = true; }
