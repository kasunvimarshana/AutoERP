<?php

namespace App\Modules\ModuleGenerator\Generators\Presentation;

use App\Modules\ModuleGenerator\Contracts\GeneratorInterface;
use Illuminate\Support\Facades\File;

class PresentationGenerator implements GeneratorInterface
{
    public function key(): string
    {
        return 'presentation';
    }

    public function generate(array $context): void
    {
        $path = $context['path'].'/Presentation/Http/Controllers';
        File::makeDirectory($path, 0755, true, true);

        File::put($path.'/Controller.php', "<?php

namespace Modules\\{$context['name']}\\Presentation\\Http\\Controllers;

class Controller {}");
    }
}
