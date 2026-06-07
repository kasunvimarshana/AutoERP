<?php
declare(strict_types=1);
namespace Modules\Hr\Services;
use Modules\Hr\Models\HrDesignation;
final class HrDesignationService extends HrMasterService { protected string $modelClass = HrDesignation::class; protected string $label = 'HR designation'; protected bool $hasSortOrder = true; }
