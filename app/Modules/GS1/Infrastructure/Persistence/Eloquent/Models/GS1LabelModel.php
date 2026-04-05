<?php
declare(strict_types=1);
namespace Modules\GS1\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class Gs1LabelModel extends BaseModel {
    protected $table = 'gs1_labels';
    protected $fillable = ['tenant_id','product_id','gs1_type','gs1_value','batch_number','lot_number',
        'serial_number','expiry_date','net_weight','country_of_origin'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','product_id'=>'int','net_weight'=>'float',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
