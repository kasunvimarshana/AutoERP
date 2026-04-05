<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Persistence\Eloquent\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Accounting\Domain\Entities\Account;
use Modules\Accounting\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Accounting\Infrastructure\Persistence\Eloquent\Models\AccountModel;
class EloquentAccountRepository implements AccountRepositoryInterface {
    public function __construct(private readonly AccountModel $model) {}
    private function toEntity(AccountModel $m): Account {
        return new Account($m->id,$m->tenant_id,$m->code,$m->name,$m->type,$m->subtype,$m->parent_id,
            (float)$m->balance,$m->currency,(bool)$m->is_active,$m->description,$m->created_at,$m->updated_at);
    }
    public function findById(int $id): ?Account { $m=$this->model->newQuery()->find($id); return $m?$this->toEntity($m):null; }
    public function findByCode(int $tenantId, string $code): ?Account { $m=$this->model->newQuery()->where('tenant_id',$tenantId)->where('code',$code)->first(); return $m?$this->toEntity($m):null; }
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator {
        return $this->model->newQuery()->where('tenant_id',$tenantId)->paginate($perPage,['*'],'page',$page)->through(fn($m)=>$this->toEntity($m));
    }
    public function create(array $data): Account { return $this->toEntity($this->model->newQuery()->create($data)); }
    public function update(int $id, array $data): ?Account { $m=$this->model->newQuery()->find($id); if(!$m)return null; $m->update($data); return $this->toEntity($m->fresh()); }
    public function updateBalance(int $id, float $balance): bool { $m=$this->model->newQuery()->find($id); if(!$m)return false; $m->update(['balance'=>$balance]); return true; }
    public function delete(int $id): bool { $m=$this->model->newQuery()->find($id); return $m?(bool)$m->delete():false; }
}
