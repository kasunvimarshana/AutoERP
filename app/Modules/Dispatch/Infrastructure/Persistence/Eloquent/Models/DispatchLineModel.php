<?php
declare(strict_types=1);
namespace Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Model;
class DispatchLineModel extends Model {
    protected $table = 'dispatch_lines';
    public $timestamps = false;
    protected $fillable = ['dispatch_id','product_id','quantity_dispatched','batch_number','lot_number','serial_number'];
    protected $casts = ['id'=>'int','dispatch_id'=>'int','product_id'=>'int','quantity_dispatched'=>'float'];
}
