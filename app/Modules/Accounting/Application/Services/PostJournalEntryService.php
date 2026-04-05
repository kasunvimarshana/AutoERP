<?php
declare(strict_types=1);
namespace Modules\Accounting\Application\Services;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Application\Contracts\PostJournalEntryServiceInterface;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Accounting\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;
class PostJournalEntryService implements PostJournalEntryServiceInterface {
    public function __construct(
        private readonly JournalEntryRepositoryInterface $jeRepo,
        private readonly AccountRepositoryInterface $accountRepo,
    ) {}
    public function execute(int $id): JournalEntry {
        return DB::transaction(function() use ($id) {
            $je=$this->jeRepo->findById($id);
            if(!$je) throw new NotFoundException("JournalEntry", $id);
            $je->post();
            $this->jeRepo->update($id,['status'=>'posted','posted_at'=>now()]);
            foreach($je->getLines() as $line) {
                $acc=$this->accountRepo->findById($line['account_id']);
                if(!$acc) continue;
                if(($line['debit']??0) > 0) { $acc->debit((float)$line['debit']); $this->accountRepo->updateBalance($acc->getId(),$acc->getBalance()); }
                if(($line['credit']??0) > 0) { $acc->credit((float)$line['credit']); $this->accountRepo->updateBalance($acc->getId(),$acc->getBalance()); }
            }
            return $this->jeRepo->findById($id);
        });
    }
}
