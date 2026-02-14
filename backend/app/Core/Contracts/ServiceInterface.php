<?php

namespace App\Core\Contracts;

/**
 * Service Interface
 * 
 * All service classes MUST implement this interface to ensure consistent
 * business logic orchestration across all modules.
 */
interface ServiceInterface
{
    /**
     * Get the repository instance
     *
     * @return RepositoryInterface
     */
    public function getRepository(): RepositoryInterface;
}
