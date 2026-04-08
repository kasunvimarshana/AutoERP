<?php

namespace App\Modules\Generator\Generators;

use Illuminate\Support\Facades\File;

class DomainGenerator
{
    public function generate(array $context): void
    {
        $path = $context['basePath'].'/Domain/Entities';

        File::makeDirectory($path, 0755, true, true);

        $content = "<?php\n\nnamespace Modules\\{$context['name']}\\Domain\\Entities;\n\nclass {$context['className']} {}";

        File::put($path.'/'.$context['className'].'.php', $content);
    }
}
