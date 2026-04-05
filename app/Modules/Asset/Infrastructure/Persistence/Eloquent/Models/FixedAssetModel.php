<?php
declare(strict_types=1);
namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Models;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
class FixedAssetModel extends BaseModel {
    protected $table = 'fixed_assets';
    protected $fillable = ['tenant_id','code','name','description','category','location_id','assigned_to','purchase_cost','residual_value','useful_life_months','depreciation_method','asset_account_id','depreciation_account_id','status','purchase_date','disposal_date'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','location_id'=>'int','assigned_to'=>'int','purchase_cost'=>'float','residual_value'=>'float','useful_life_months'=>'int','asset_account_id'=>'int','depreciation_account_id'=>'int','purchase_date'=>'date','disposal_date'=>'date','created_at'=>'datetime','updated_at'=>'datetime','deleted_at'=>'datetime'];
}
