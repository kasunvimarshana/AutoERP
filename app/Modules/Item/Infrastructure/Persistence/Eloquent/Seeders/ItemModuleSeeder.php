<?php

declare(strict_types=1);

namespace Modules\Item\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;

final class ItemModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Modules\Item\Infrastructure\Persistence\Eloquent\Seeders\ItemTypesSeeder::class,
            \Modules\Item\Infrastructure\Persistence\Eloquent\Seeders\ItemSampleSeeder::class,
        ]);
    }
}
