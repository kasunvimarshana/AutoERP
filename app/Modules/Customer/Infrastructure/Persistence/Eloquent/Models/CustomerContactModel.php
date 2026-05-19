<?php

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerContactModel extends Model
{
    protected $table = 'customer_contacts';
    protected $guarded = [];
}
