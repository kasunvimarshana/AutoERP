<?php
declare(strict_types=1);
namespace Modules\Hr\Services;
use Modules\Hr\Models\HrCertification;
final class HrCertificationService extends HrMasterService { protected string $modelClass = HrCertification::class; protected string $label = 'HR certification'; }
