<?php

namespace App\Policies;

class ProductPolicy extends BasePolicy
{
    protected function module(): string
    {
        return 'products';
    }
}
