<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\ApiDoc\Contracts;

/**
 * Contract for the API documentation generation service.
 *
 * Implementations are responsible for generating and caching
 * the OpenAPI specification document from annotated source files.
 */
interface ApiDocServiceInterface
{
    /**
     * Generate (or retrieve cached) OpenAPI specification as a JSON string.
     */
    public function generate(string $documentation = 'default'): string;

    /**
     * Return the URL at which the Swagger UI is accessible.
     */
    public function uiUrl(string $documentation = 'default'): string;

    /**
     * Return the URL at which the raw OpenAPI JSON spec is accessible.
     */
    public function specUrl(string $documentation = 'default'): string;
}
