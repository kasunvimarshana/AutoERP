<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers\Api;

use App\Application\Catalog\Commands\CreateProductCommand;
use App\Application\Catalog\Handlers\CreateProductHandler;
use App\Application\Catalog\Handlers\GetProductQueryHandler;
use App\Application\Catalog\Queries\GetProductQuery;
use App\Domain\Catalog\Exceptions\InvalidProductException;
use App\Presentation\Http\Requests\StoreProductRequest;
use App\Presentation\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use App\Domain\Catalog\Repositories\ProductRepositoryInterface;

/**
 * ProductController — Thin API controller for the Catalog context.
 *
 * Responsibility: receive HTTP input, delegate to Application handlers,
 * and return HTTP responses. No business logic lives here.
 */
final class ProductController
{
    public function __construct(
        private readonly CreateProductHandler    $createHandler,
        private readonly GetProductQueryHandler  $getHandler,
        private readonly ProductRepositoryInterface $repository,
    ) {}

    /**
     * GET /api/v1/products
     */
    public function index(): AnonymousResourceCollection
    {
        $products = $this->repository->findAll();

        return ProductResource::collection($products);
    }

    /**
     * POST /api/v1/products
     */
    public function store(StoreProductRequest $request): ProductResource
    {
        $command = new CreateProductCommand(
            name:          $request->validated('name'),
            priceAmount:   (int) $request->validated('price_amount'),
            priceCurrency: $request->validated('price_currency'),
        );

        $product = $this->createHandler->handle($command);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/products/{id}
     */
    public function show(string $id): ProductResource|JsonResponse
    {
        try {
            $product = $this->getHandler->handle(new GetProductQuery($id));

            return new ProductResource($product);
        } catch (InvalidProductException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => 'Invalid product ID format.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
