<?php

namespace App\Modules\ModuleGenerator\Generators\Domain;

use App\Modules\ModuleGenerator\Contracts\GeneratorInterface;
use Illuminate\Support\Facades\File;

class EntityGenerator implements GeneratorInterface
{
    public function key(): string
    {
        return 'domain';
    }

    public function generate(array $context): void
    {
        $path = $context['path'].'/Domain/Entities';
        File::makeDirectory($path, 0755, true, true);

        File::put($path.'/'.$context['name'].'.php', "<?php

namespace Modules\\{$context['name']}\\Domain\\Entities;

class {$context['name']} {}");
    }
}
