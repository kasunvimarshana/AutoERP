<?php

namespace Modules\POS\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\POS\Domain\Contracts\PosSessionRepositoryInterface;
use Modules\POS\Domain\Events\PosSessionClosed;
use Modules\Shared\Domain\Contracts\UseCaseInterface;

class CloseSessionUseCase implements UseCaseInterface
{
    public function __construct(
        private PosSessionRepositoryInterface $sessionRepo,
    ) {}

    public function execute(array $data): mixed
    {
        $session = $this->sessionRepo->findById($data['session_id']);
        if (!$session) {
            throw new \DomainException('Session not found.');
        }

        if ($session->status !== 'open') {
            throw new \DomainException('Only open sessions can be closed.');
        }

        return DB::transaction(function () use ($data, $session) {
            $updated = $this->sessionRepo->update($session->id, [
                'status'       => 'closed',
                'closing_cash' => $data['closing_cash'] ?? '0.00000000',
                'closed_at'    => now(),
            ]);

            Event::dispatch(new PosSessionClosed($session->id));

            return $updated;
        });
    }
}
