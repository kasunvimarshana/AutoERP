<?php
declare(strict_types=1);
namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class OrgUnitClosureModel extends Model
{
    protected $table = 'org_unit_closures';
    public $timestamps = false;
    protected $fillable = ['ancestor_id', 'descendant_id', 'depth'];
    protected $casts = [
        'ancestor_id' => 'int',
        'descendant_id' => 'int',
        'depth' => 'int',
    ];
}
