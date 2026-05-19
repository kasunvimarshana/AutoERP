<?php

namespace Modules\Audit\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentAttachmentModel extends Model
{
    protected $table = 'audit_logs';
    protected $guarded = [];
}
