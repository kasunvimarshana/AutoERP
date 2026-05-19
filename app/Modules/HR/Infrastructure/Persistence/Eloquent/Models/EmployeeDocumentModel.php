<?php

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocumentModel extends Model
{
    protected $table = 'employee_documents';
    protected $guarded = [];
}
