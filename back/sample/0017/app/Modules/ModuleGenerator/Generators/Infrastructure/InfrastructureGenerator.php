<?php

namespace App\Modules\ModuleGenerator\Generators\Infrastructure;

use App\Modules\ModuleGenerator\Contracts\GeneratorInterface;
use Illuminate\Support\Facades\File;

class InfrastructureGenerator implements GeneratorInterface
{
    public function key(): string
    {
        return 'infrastructure';
    }

    public function generate(array $context): void
    {
        $path = $context['path'].'/Infrastructure/Providers';
        File::makeDirectory($path, 0755, true, true);

        File::put($path.'/ModuleServiceProvider.php', "<?php

namespace Modules\\{$context['name']}\\Infrastructure\\Providers;

use Illuminate\\Support\\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider {}");
    }
}
