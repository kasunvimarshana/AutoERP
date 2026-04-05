<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;

class BarcodePrintJobModel extends BaseModel
{
    protected $table = 'barcode_print_jobs';

    protected $fillable = [
        'tenant_id', 'barcode_definition_id', 'label_template_id',
        'status', 'printer_target', 'copies',
        'rendered_output', 'variables', 'error_message',
        'queued_at', 'completed_at',
    ];

    protected $casts = [
        'id'                    => 'int',
        'tenant_id'             => 'int',
        'barcode_definition_id' => 'int',
        'label_template_id'     => 'int',
        'copies'                => 'int',
        'variables'             => 'array',
        'queued_at'             => 'datetime',
        'completed_at'          => 'datetime',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
        'deleted_at'            => 'datetime',
    ];
}
