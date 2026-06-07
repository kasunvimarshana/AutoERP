<?php
declare(strict_types=1);
namespace Modules\Hr\Enums;
enum EmployeeStatus: string { case Active = 'active'; case Inactive = 'inactive'; case OnLeave = 'on_leave'; case Suspended = 'suspended'; case Terminated = 'terminated'; case PendingApproval = 'pending_approval'; }
