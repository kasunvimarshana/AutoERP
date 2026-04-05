<?php
declare(strict_types=1);
namespace Modules\POS\Application\Services;

use Modules\POS\Domain\Entities\PosSession;
use Modules\POS\Domain\Exceptions\PosSessionNotFoundException;
use Modules\POS\Domain\RepositoryInterfaces\PosSessionRepositoryInterface;

class CloseSessionService
{
    public function __construct(
        private readonly PosSessionRepositoryInterface $sessionRepository,
    ) {}

    public function close(int $sessionId, float $closingBalance, ?string $notes = null): PosSession
    {
        $session = $this->sessionRepository->findById($sessionId);
        if ($session === null) {
            throw new PosSessionNotFoundException($sessionId);
        }

        $session->close($closingBalance, $notes);

        return $this->sessionRepository->update($sessionId, [
            'status'          => PosSession::STATUS_CLOSED,
            'closing_balance' => $closingBalance,
            'notes'           => $notes,
            'closed_at'       => new \DateTimeImmutable(),
        ]) ?? $session;
    }
}
