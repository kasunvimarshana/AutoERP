<?php

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceLineModel extends Model
{
    protected $table = 'invoice_lines';
    protected $guarded = [];
}
