<?php

declare(strict_types=1);

namespace App\Modules\CRM\Repositories;

use App\Core\Repositories\BaseRepositoryInterface;
use App\Modules\CRM\Models\Contact;
use Illuminate\Database\Eloquent\Collection;

interface ContactRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get contacts for a customer
     */
    public function getByCustomer(int $customerId): Collection;

    /**
     * Get primary contact for a customer
     */
    public function getPrimaryContact(int $customerId): ?Contact;

    /**
     * Get active contacts for a customer
     */
    public function getActiveContactsByCustomer(int $customerId): Collection;

    /**
     * Find contact by email
     */
    public function findByEmail(string $email): ?Contact;

    /**
     * Get decision makers for a customer
     */
    public function getDecisionMakers(int $customerId): Collection;

    /**
     * Search contacts
     */
    public function search(string $query, array $filters = []): Collection;
}
