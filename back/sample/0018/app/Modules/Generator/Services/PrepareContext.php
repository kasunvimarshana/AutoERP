<?php

namespace App\Modules\Generator\Services;

class PrepareContext
{
    public function handle(array &$context): void
    {
        $context['className'] = ucfirst($context['name']);
    }
}
