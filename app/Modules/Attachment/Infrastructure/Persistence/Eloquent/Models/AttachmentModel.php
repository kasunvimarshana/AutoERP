<?php
declare(strict_types=1);
namespace Modules\Attachment\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class AttachmentModel extends BaseModel {
    protected $table = 'attachments';
    protected $fillable = ['tenant_id','attachable_type','attachable_id','filename','original_name','mime_type','size','path','disk','category','uploaded_by'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','attachable_id'=>'int','size'=>'int','uploaded_by'=>'int',
        'created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
