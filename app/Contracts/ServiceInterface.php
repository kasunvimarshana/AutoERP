<?php

namespace App\Contracts;

/**
 * Base Service Interface
 *
 * Defines the contract for service layer implementations.
 * Services orchestrate business logic, handle transactions,
 * and coordinate interactions between repositories and modules.
 */
interface ServiceInterface
{
    /**
     * Set the repository instance
     */
    public function setRepository(RepositoryInterface $repository): self;

    /**
     * Get the repository instance
     */
    public function getRepository(): RepositoryInterface;

    /**
     * Execute a callback within a database transaction
     *
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(callable $callback);

    /**
     * Log an activity for audit trail
     */
    public function logActivity(string $action, array $context = []): void;
}
