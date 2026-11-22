<?php
declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Enums;
enum EmployeeAddressType: string { case Permanent = 'permanent'; case Current = 'current'; case Work = 'work'; case Other = 'other'; }
