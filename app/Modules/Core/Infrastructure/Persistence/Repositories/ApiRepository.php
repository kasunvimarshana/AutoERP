<?php

declare(strict_types=1);

namespace Modules\Core\Infrastructure\Persistence\Repositories;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class ApiRepository extends BaseRepository
{
    protected PendingRequest $http;

    protected string $endpoint;

    protected array $defaultParams;

    public function __construct(PendingRequest $http, string $endpoint, array $defaultParams = [])
    {
        $this->http = $http;
        $this->endpoint = $endpoint;
        $this->defaultParams = $defaultParams;
        $this->provider = $this; // self‑handling
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, array $columns = ['*'])
    {
        $params = $this->buildQueryParams($columns);
        $response = $this->http->get("{$this->endpoint}/{$id}", $params);

        return $response->successful() ? $response->json('data') : null;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $columns = ['*']): Collection
    {
        $params = $this->buildQueryParams($columns);
        $response = $this->http->get($this->endpoint, $params);

        return $response->successful() ? new Collection($response->json('data') ?? []) : new Collection;
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(?int $perPage = null, array $columns = ['*'], ?string $pageName = null, ?int $page = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('core.pagination.per_page', 15);
        $pageName = $pageName ?? config('core.pagination.page_name', 'page');

        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $params = array_merge($this->buildQueryParams($columns), [
            'per_page' => $perPage,
            $pageName => $page,
        ]);

        $response = $this->http->get($this->endpoint, $params);
        $data = $response->json();

        $items = new Collection($data['data'] ?? []);
        $total = $data['meta']['total'] ?? 0;
        $perPage = $data['meta']['per_page'] ?? $perPage;
        $currentPage = $data['meta']['current_page'] ?? $page;

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => $pageName]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        $response = $this->http->post($this->endpoint, $data);

        return $response->successful() ? $response->json('data') : null;
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, array $data)
    {
        $response = $this->http->put("{$this->endpoint}/{$id}", $data);

        return $response->successful() ? $response->json('data') : null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id): bool
    {
        $response = $this->http->delete("{$this->endpoint}/{$id}");

        return $response->successful();
    }

    /**
     * {@inheritdoc}
     */
    protected function applyCriteria(): void
    {
        // No operation – criteria are applied in buildQueryParams
    }

    /**
     * {@inheritdoc}
     */
    protected function resetProvider(): void
    {
        // Not needed
    }

    /**
     * Build query parameters from stored criteria.
     */
    protected function buildQueryParams(array $columns): array
    {
        $params = $this->defaultParams;

        // Fields to select
        if ($columns !== ['*']) {
            $params['fields'] = implode(',', $columns);
        }

        // Eager loading (include)
        if (! empty($this->with)) {
            $params['include'] = implode(',', $this->with);
        }

        // Where clauses – convert to filter[column]=value
        foreach ($this->wheres as $where) {
            // For simplicity, we only send the value; API should understand operator.
            $params["filter[{$where['column']}]"] = $where['value'] ?? $where['operator'];
        }

        // WhereIn – send as comma‑separated list
        foreach ($this->whereIns as $whereIn) {
            $params["filter[{$whereIn['column']}]"] = implode(',', $whereIn['values']);
        }

        // WhereBetween – send as from/to parameters
        foreach ($this->whereBetweens as $whereBetween) {
            $params["filter[{$whereBetween['column']}_from]"] = $whereBetween['values'][0] ?? null;
            $params["filter[{$whereBetween['column']}_to]"] = $whereBetween['values'][1] ?? null;
        }

        // WhereNull – send flag
        foreach ($this->whereNulls as $whereNull) {
            $params["filter[{$whereNull['column']}_is_null]"] = $whereNull['not'] ? 'false' : 'true';
        }

        // Sorting
        if (! empty($this->orders)) {
            $params['sort'] = [];
            foreach ($this->orders as $order) {
                $params['sort'][] = ($order['direction'] === 'desc' ? '-' : '').$order['column'];
            }
        }

        // Raw order by – not supported; ignore.

        // Limit and offset
        if ($this->limit !== null) {
            $params['limit'] = $this->limit;
        }
        if ($this->offset !== null) {
            $params['offset'] = $this->offset;
        }

        return $params;
    }
}
