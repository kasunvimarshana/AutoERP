<?php

namespace Modules\Notification\Domain\Contracts;

interface NotificationRepositoryInterface
{
    /** Return a paginated list of notifications for a given user (ordered newest-first). */
    public function paginateForUser(string $userId, int $perPage = 20): object;

    /** Find a single notification by its UUID. */
    public function findById(string $id): ?object;

    /** Find a notification by ID that belongs to a specific user. */
    public function findByIdAndUser(string $id, string $userId): ?object;

    /** Mark a notification as read. */
    public function markRead(string $id): void;

    /** Mark all unread notifications for a user as read. */
    public function markAllReadForUser(string $userId): void;

    /** Count unread notifications for a user. */
    public function countUnreadForUser(string $userId): int;

    /** Delete a notification record. */
    public function delete(string $id): void;
}
