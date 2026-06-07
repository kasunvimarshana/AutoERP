<?php
declare(strict_types=1);
namespace Modules\Hr\Enums;
enum EmployeeAvailabilityStatus: string { case Available = 'available'; case Assigned = 'assigned'; case OnLeave = 'on_leave'; case Unavailable = 'unavailable'; case Suspended = 'suspended'; case Inactive = 'inactive'; }
