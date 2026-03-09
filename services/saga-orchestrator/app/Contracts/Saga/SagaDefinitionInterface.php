<?php

declare(strict_types=1);

namespace App\Contracts\Saga;

/**
 * Saga Definition Interface
 *
 * Each saga type (CreateOrder, TransferStock, etc.) implements this
 * to define its steps and compensation steps.
 */
interface SagaDefinitionInterface
{
    /**
     * Get the saga type identifier.
     */
    public function getType(): string;

    /**
     * Build the ordered list of saga steps.
     *
     * @param  array<string, mixed>  $payload
     * @return array<int, array{
     *   step_name: string,
     *   service: string,
     *   endpoint: string,
     *   compensation_endpoint: string,
     *   request_payload: array
     * }>
     */
    public function buildSteps(array $payload): array;
}
