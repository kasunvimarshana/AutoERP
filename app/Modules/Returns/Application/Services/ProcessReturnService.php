<?php
declare(strict_types=1);
namespace Modules\Returns\Application\Services;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Returns\Application\Contracts\ProcessReturnServiceInterface;
use Modules\Returns\Domain\Entities\ReturnRequest;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnRequestRepositoryInterface;
class ProcessReturnService implements ProcessReturnServiceInterface {
    public function __construct(private readonly ReturnRequestRepositoryInterface $repo) {}
    public function approve(int $id, int $processedBy): ReturnRequest {
        $ret=$this->repo->findById($id);
        if(!$ret) throw new NotFoundException("ReturnRequest", $id);
        $ret->approve($processedBy);
        $this->repo->update($id,['status'=>'approved','processed_by'=>$processedBy,'processed_at'=>now()]);
        return $this->repo->findById($id);
    }
    public function reject(int $id, int $processedBy): ReturnRequest {
        $ret=$this->repo->findById($id);
        if(!$ret) throw new NotFoundException("ReturnRequest", $id);
        $ret->reject($processedBy);
        $this->repo->update($id,['status'=>'rejected','processed_by'=>$processedBy,'processed_at'=>now()]);
        return $this->repo->findById($id);
    }
}
