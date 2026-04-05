<?php
declare(strict_types=1);
namespace Modules\POS\Application\Services;

use Modules\POS\Domain\Entities\PosSession;
use Modules\POS\Domain\Exceptions\PosTerminalNotFoundException;
use Modules\POS\Domain\RepositoryInterfaces\PosSessionRepositoryInterface;
use Modules\POS\Domain\RepositoryInterfaces\PosTerminalRepositoryInterface;

class OpenSessionService
{
    public function __construct(
        private readonly PosTerminalRepositoryInterface $terminalRepository,
        private readonly PosSessionRepositoryInterface $sessionRepository,
    ) {}

    public function open(int $terminalId, int $cashierId, float $openingBalance): PosSession
    {
        $terminal = $this->terminalRepository->findById($terminalId);
        if ($terminal === null) {
            throw new PosTerminalNotFoundException($terminalId);
        }
        if (!$terminal->isActive()) {
            throw new \DomainException("Terminal {$terminalId} is inactive.");
        }

        $existing = $this->sessionRepository->findOpenByTerminal($terminalId);
        if ($existing !== null) {
            throw new \DomainException("Terminal {$terminalId} already has an open session (ID: {$existing->getId()}).");
        }

        return $this->sessionRepository->create([
            'tenant_id'        => $terminal->getTenantId(),
            'terminal_id'      => $terminalId,
            'cashier_id'       => $cashierId,
            'status'           => PosSession::STATUS_OPEN,
            'opening_balance'  => $openingBalance,
            'closing_balance'  => null,
            'notes'            => null,
            'opened_at'        => new \DateTimeImmutable(),
        ]);
    }
}
