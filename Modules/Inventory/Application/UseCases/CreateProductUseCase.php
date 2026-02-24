<?php
namespace Modules\Inventory\Application\UseCases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Inventory\Domain\Contracts\ProductRepositoryInterface;
use Modules\Inventory\Domain\Events\ProductCreated;
class CreateProductUseCase
{
    public function __construct(private ProductRepositoryInterface $repo) {}
    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $product = $this->repo->create($data);
            Event::dispatch(new ProductCreated($product->id));
            return $product;
        });
    }
}
