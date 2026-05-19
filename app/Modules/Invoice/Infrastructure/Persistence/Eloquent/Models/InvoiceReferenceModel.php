<?php

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceReferenceModel extends Model
{
    protected $table = 'invoice_references';
    protected $guarded = [];
}
