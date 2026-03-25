<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\ApiDoc\Services;

use L5Swagger\Generator;
use Modules\Core\Infrastructure\ApiDoc\Contracts\ApiDocServiceInterface;

/**
 * L5-Swagger-backed implementation of {@see ApiDocServiceInterface}.
 *
 * This service delegates spec generation to the darkaonline/l5-swagger
 * package and exposes helper methods for retrieving UI/spec URLs.
 */
class SwaggerApiDocService implements ApiDocServiceInterface
{
    public function __construct(private readonly Generator $generator) {}

    /**
     * {@inheritdoc}
     */
    public function generate(string $documentation = 'default'): string
    {
        $this->generator->generateDocs($documentation);

        $docsPath = storage_path('api-docs/api-docs.json');

        return file_exists($docsPath) ? (string) file_get_contents($docsPath) : '{}';
    }

    /**
     * {@inheritdoc}
     */
    public function uiUrl(string $documentation = 'default'): string
    {
        return url(config('l5-swagger.documentations.'.$documentation.'.routes.api', 'api/documentation'));
    }

    /**
     * {@inheritdoc}
     */
    public function specUrl(string $documentation = 'default'): string
    {
        $docsRoute = config('l5-swagger.defaults.routes.docs', 'docs');

        return url($docsRoute.'/api-docs.json');
    }
}
