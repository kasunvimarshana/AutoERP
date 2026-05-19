<?php

namespace Modules\User\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class UserDocumentModel extends Model
{
    protected $table = 'user_documents';
    protected $guarded = [];
}
