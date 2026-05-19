<?php

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptInspectionModel extends Model
{
    protected $table = 'receipt_inspections';
    protected $guarded = [];
}
