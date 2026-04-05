<?php
declare(strict_types=1);
namespace Modules\User\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

class UserCreated extends BaseEvent
{
    public int $userId;
    public string $email;

    public function __construct(int $tenantId, int $userId, string $email)
    {
        parent::__construct($tenantId);
        $this->userId = $userId;
        $this->email = $email;
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'userId' => $this->userId,
            'email' => $this->email,
        ]);
    }
}
