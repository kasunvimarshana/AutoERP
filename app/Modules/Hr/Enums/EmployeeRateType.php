<?php
declare(strict_types=1);
namespace Modules\Hr\Enums;
enum EmployeeRateType: string { case Hourly = 'hourly'; case Daily = 'daily'; case Monthly = 'monthly'; case Service = 'service'; case Fixed = 'fixed'; case Commission = 'commission'; }
