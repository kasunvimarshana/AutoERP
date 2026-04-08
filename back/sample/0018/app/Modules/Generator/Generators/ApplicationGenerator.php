<?php

namespace App\Modules\Generator\Generators;

use Illuminate\Support\Facades\File;

class ApplicationGenerator
{
    public function generate(array $context): void
    {
        $path = $context['basePath'].'/Application/Services';

        File::makeDirectory($path, 0755, true, true);

        File::put($path.'/Service.php', "<?php\n\nnamespace Modules\\{$context['name']}\\Application\\Services;\n\nclass Service {}");
    }
}
