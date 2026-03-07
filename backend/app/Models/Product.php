<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends \App\Domain\Inventory\Entities\Product
{
    // Inherits all logic from the Domain entity.
    // This model exists so the App\Models namespace remains consistent
    // with Laravel conventions and policy registration.
}
