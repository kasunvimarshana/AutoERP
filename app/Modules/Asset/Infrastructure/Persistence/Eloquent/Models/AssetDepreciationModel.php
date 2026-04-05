<?php
declare(strict_types=1);
namespace Modules\Asset\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Model;
class AssetDepreciationModel extends Model {
    public const UPDATED_AT = null;
    protected $table = 'asset_depreciations';
    protected $fillable = ['tenant_id','asset_id','type','period_year','period_month','amount','book_value_before','book_value_after','journal_entry_id','depreciated_at'];
    protected $casts = ['id'=>'int','tenant_id'=>'int','asset_id'=>'int','period_year'=>'int','period_month'=>'int','amount'=>'float','book_value_before'=>'float','book_value_after'=>'float','journal_entry_id'=>'int','depreciated_at'=>'datetime','created_at'=>'datetime'];
}
