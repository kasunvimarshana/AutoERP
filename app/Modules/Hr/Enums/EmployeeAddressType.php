<?php
declare(strict_types=1);
namespace Modules\Hr\Enums;
enum EmployeeAddressType: string { case Permanent = 'permanent'; case Current = 'current'; case Work = 'work'; case Other = 'other'; }
