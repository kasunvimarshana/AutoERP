<?php
namespace Modules\GS1\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class GS1BarcodeModel extends BaseModel
{
    protected $table = 'gs1_barcodes';

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
