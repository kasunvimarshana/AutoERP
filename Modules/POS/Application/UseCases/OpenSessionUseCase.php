<?php

namespace Modules\POS\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\POS\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\POS\Domain\Events\PosSessionOpened;
use Modules\Shared\Domain\Contracts\UseCaseInterface;

class OpenSessionUseCase implements UseCaseInterface
{
    public function __construct(
        private PosSessionRepositoryInterface $sessionRepo,
    ) {}

    public function execute(array $data): mixed
    {
        $tenantId = auth()->user()?->tenant_id ?? $data['tenant_id'] ?? null;
        $terminalId = $data['terminal_id'];

        $existing = $this->sessionRepo->findOpenByTerminal($tenantId, $terminalId);
        if ($existing) {
            throw new \DomainException('Terminal already has an open session.');
        }

        return DB::transaction(function () use ($data, $tenantId) {
            $session = $this->sessionRepo->create([
                'tenant_id'    => $tenantId,
                'terminal_id'  => $data['terminal_id'],
                'cashier_id'   => $data['cashier_id'] ?? auth()->id(),
                'status'       => 'open',
                'opening_cash' => $data['opening_cash'] ?? '0.00000000',
                'opened_at'    => now(),
            ]);

            Event::dispatch(new PosSessionOpened($session->id));

            return $session;
        });
    }
}
