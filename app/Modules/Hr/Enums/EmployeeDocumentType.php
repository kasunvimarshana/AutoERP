<?php
declare(strict_types=1);
namespace Modules\Hr\Enums;
enum EmployeeDocumentType: string { case IdDocument = 'id_document'; case Contract = 'contract'; case Certification = 'certification'; case License = 'license'; case Medical = 'medical'; case EmploymentLetter = 'employment_letter'; case Other = 'other'; }
