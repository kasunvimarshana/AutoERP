<?php
namespace Modules\Configuration\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class OrgUnitClosureModel extends Model
{
    protected $table = 'organization_unit_closures';
    public $timestamps = false;
    protected $fillable = ['ancestor_id', 'descendant_id', 'depth'];
}
