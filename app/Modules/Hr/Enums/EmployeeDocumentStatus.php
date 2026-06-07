<?php
declare(strict_types=1);
namespace Modules\Hr\Enums;
enum EmployeeDocumentStatus: string { case Active = 'active'; case Expired = 'expired'; case Revoked = 'revoked'; case Pending = 'pending'; }
