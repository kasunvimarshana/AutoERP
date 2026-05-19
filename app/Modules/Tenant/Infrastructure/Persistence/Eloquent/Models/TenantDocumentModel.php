<?php

namespace Modules\Tenant\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class TenantDocumentModel extends Model
{
    protected $table = 'tenant_documents';
    protected $guarded = [];
}
