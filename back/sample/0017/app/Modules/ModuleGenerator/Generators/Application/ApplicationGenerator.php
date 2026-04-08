<?php

namespace App\Modules\ModuleGenerator\Generators\Application;

use App\Modules\ModuleGenerator\Contracts\GeneratorInterface;
use Illuminate\Support\Facades\File;

class ApplicationGenerator implements GeneratorInterface
{
    public function key(): string
    {
        return 'application';
    }

    public function generate(array $context): void
    {
        $path = $context['path'].'/Application/Services';
        File::makeDirectory($path, 0755, true, true);

        File::put($path.'/ExampleService.php', "<?php

namespace Modules\\{$context['name']}\\Application\\Services;

class ExampleService {}");
    }
}
