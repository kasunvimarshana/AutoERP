<?php
namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategoryClosureModel extends Model
{
    protected $table = 'product_category_closures';
    public $timestamps = false;
    protected $fillable = ['ancestor_id', 'descendant_id', 'depth'];
}
