<?php
declare(strict_types=1);
namespace Modules\Currency\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyModel extends Model
{
    protected $table = 'currencies';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code', 'name', 'symbol', 'decimal_places', 'is_base', 'is_active'];
    protected $casts = [
        'decimal_places' => 'int',
        'is_base'        => 'bool',
        'is_active'      => 'bool',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];
}
