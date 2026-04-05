<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Domain\Entities\JournalEntry;
use Modules\Accounting\Domain\RepositoryInterfaces\JournalEntryRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\JournalEntryLineModel;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\JournalEntryModel;
class EloquentJournalEntryRepository implements JournalEntryRepositoryInterface {
    public function __construct(private readonly JournalEntryModel $model, private readonly JournalEntryLineModel $lineModel) {}
    private function toEntity(JournalEntryModel $m): JournalEntry {
        return new JournalEntry($m->id,$m->tenant_id,$m->entry_number,$m->status,$m->description,$m->currency,
            (float)$m->total_debit,(float)$m->total_credit,$m->reference,$m->created_by,$m->lines->toArray(),$m->posted_at,$m->created_at,$m->updated_at);
    }
    public function findById(int $id): ?JournalEntry { $m=$this->model->newQuery()->with('lines')->find($id); return $m?$this->toEntity($m):null; }
    public function findByTenant(int $tenantId, array $filters = [], int $perPage = 15, int $page = 1): LengthAwarePaginator {
        $q=$this->model->newQuery()->with('lines')->where('tenant_id',$tenantId);
        if(!empty($filters['status'])) $q->where('status',$filters['status']);
        return $q->paginate($perPage,['*'],'page',$page)->through(fn($m)=>$this->toEntity($m));
    }
    public function create(array $data, array $lines): JournalEntry {
        return DB::transaction(function() use ($data,$lines) {
            $m=$this->model->newQuery()->create($data);
            foreach($lines as $l) $this->lineModel->newQuery()->create(array_merge($l,['journal_entry_id'=>$m->id]));
            return $this->findById($m->id);
        });
    }
    public function update(int $id, array $data): ?JournalEntry { $m=$this->model->newQuery()->find($id); if(!$m)return null; $m->update($data); return $this->findById($id); }
    public function delete(int $id): bool { $m=$this->model->newQuery()->find($id); return $m?(bool)$m->delete():false; }
}
