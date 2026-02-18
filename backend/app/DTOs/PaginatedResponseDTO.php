<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * Paginated Response DTO
 * 
 * Standardized pagination response structure for all API endpoints
 */
class PaginatedResponseDTO extends BaseDTO
{
    public array $data;
    public int $currentPage;
    public int $perPage;
    public int $total;
    public int $lastPage;
    public ?string $nextPageUrl;
    public ?string $prevPageUrl;
    public int $from;
    public int $to;

    /**
     * Create from Laravel paginator
     */
    public static function fromPaginator($paginator): static
    {
        $dto = new static();
        $dto->data = $paginator->items();
        $dto->currentPage = $paginator->currentPage();
        $dto->perPage = $paginator->perPage();
        $dto->total = $paginator->total();
        $dto->lastPage = $paginator->lastPage();
        $dto->nextPageUrl = $paginator->nextPageUrl();
        $dto->prevPageUrl = $paginator->previousPageUrl();
        $dto->from = $paginator->firstItem() ?? 0;
        $dto->to = $paginator->lastItem() ?? 0;

        return $dto;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'pagination' => [
                'current_page' => $this->currentPage,
                'per_page' => $this->perPage,
                'total' => $this->total,
                'last_page' => $this->lastPage,
                'from' => $this->from,
                'to' => $this->to,
            ],
            'links' => [
                'next' => $this->nextPageUrl,
                'prev' => $this->prevPageUrl,
            ],
        ];
    }
}
