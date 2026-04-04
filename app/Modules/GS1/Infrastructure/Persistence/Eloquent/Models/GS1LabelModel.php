<?php
namespace Modules\GS1\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class GS1LabelModel extends BaseModel
{
    protected $table = 'gs1_labels';

    protected $casts = [
        'generated_at' => 'datetime',
    ];
}
