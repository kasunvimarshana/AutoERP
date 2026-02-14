<?php

namespace App\DTOs;

/**
 * Query Configuration DTO
 * 
 * Encapsulates all query parameters for dynamic, configuration-driven queries
 * 
 * @package App\DTOs
 */
class QueryConfig
{
    /**
     * Columns to select (sparse field selection)
     * 
     * @var array
     */
    public array $select = ['*'];

    /**
     * Search configuration
     * Format: ['query' => 'search term', 'fields' => ['field1', 'field2']]
     * 
     * @var array|null
     */
    public ?array $search = null;

    /**
     * Filters configuration
     * Format: ['field' => 'value'] or ['field' => ['operator' => '=', 'value' => 'x']]
     * 
     * @var array
     */
    public array $filters = [];

    /**
     * Relation filters configuration
     * Format: ['relation.field' => 'value']
     * 
     * @var array
     */
    public array $relationFilters = [];

    /**
     * Sorting configuration
     * Format: ['field' => 'asc'] or [['field' => 'name', 'direction' => 'desc']]
     * 
     * @var array
     */
    public array $sort = [];

    /**
     * Pagination configuration
     * 
     * @var int|null
     */
    public ?int $perPage = null;

    /**
     * Current page for pagination
     * 
     * @var int
     */
    public int $page = 1;

    /**
     * Relations to eager load
     * Format: ['relation1', 'relation2'] or ['relation1:field1,field2']
     * 
     * @var array
     */
    public array $with = [];

    /**
     * Relations count to include
     * Format: ['relation1', 'relation2']
     * 
     * @var array
     */
    public array $withCount = [];

    /**
     * Whether to use pagination
     * 
     * @var bool
     */
    public bool $paginate = true;

    /**
     * Tenant ID for tenant-aware queries
     * 
     * @var int|null
     */
    public ?int $tenantId = null;

    /**
     * Create QueryConfig from request parameters
     *
     * @param array $params
     * @return self
     */
    public static function fromRequest(array $params): self
    {
        $config = new self();

        // Select fields
        if (isset($params['select'])) {
            $config->select = is_array($params['select']) 
                ? $params['select'] 
                : explode(',', $params['select']);
        }

        // Search
        if (isset($params['search']) && !empty($params['search'])) {
            $config->search = [
                'query' => $params['search'],
                'fields' => isset($params['search_fields']) 
                    ? (is_array($params['search_fields']) ? $params['search_fields'] : explode(',', $params['search_fields']))
                    : []
            ];
        }

        // Filters
        if (isset($params['filters']) && is_array($params['filters'])) {
            $config->filters = $params['filters'];
        }

        // Relation filters
        if (isset($params['relation_filters']) && is_array($params['relation_filters'])) {
            $config->relationFilters = $params['relation_filters'];
        }

        // Sorting
        if (isset($params['sort'])) {
            if (is_array($params['sort'])) {
                $config->sort = $params['sort'];
            } else {
                // Support sort=field:direction format
                $sorts = explode(',', $params['sort']);
                foreach ($sorts as $sort) {
                    if (str_contains($sort, ':')) {
                        [$field, $direction] = explode(':', $sort);
                        $config->sort[$field] = $direction;
                    } else {
                        $config->sort[$sort] = 'asc';
                    }
                }
            }
        }

        // Pagination
        if (isset($params['per_page'])) {
            $config->perPage = (int) $params['per_page'];
        }

        if (isset($params['page'])) {
            $config->page = (int) $params['page'];
        }

        if (isset($params['paginate'])) {
            $config->paginate = filter_var($params['paginate'], FILTER_VALIDATE_BOOLEAN);
        }

        // Eager loading
        if (isset($params['with'])) {
            $config->with = is_array($params['with']) 
                ? $params['with'] 
                : explode(',', $params['with']);
        }

        // Count relations
        if (isset($params['with_count'])) {
            $config->withCount = is_array($params['with_count']) 
                ? $params['with_count'] 
                : explode(',', $params['with_count']);
        }

        // Tenant ID
        if (isset($params['tenant_id'])) {
            $config->tenantId = (int) $params['tenant_id'];
        }

        return $config;
    }

    /**
     * Validate query configuration
     *
     * @param array $allowedFields
     * @param array $allowedRelations
     * @return array Validation errors (empty if valid)
     */
    public function validate(array $allowedFields = [], array $allowedRelations = []): array
    {
        $errors = [];

        // Validate select fields
        if (!empty($allowedFields) && $this->select !== ['*']) {
            foreach ($this->select as $field) {
                if (!in_array($field, $allowedFields)) {
                    $errors[] = "Invalid select field: {$field}";
                }
            }
        }

        // Validate search fields
        if ($this->search && !empty($allowedFields)) {
            foreach ($this->search['fields'] as $field) {
                if (!in_array($field, $allowedFields)) {
                    $errors[] = "Invalid search field: {$field}";
                }
            }
        }

        // Validate filter fields
        if (!empty($allowedFields)) {
            foreach (array_keys($this->filters) as $field) {
                if (!in_array($field, $allowedFields)) {
                    $errors[] = "Invalid filter field: {$field}";
                }
            }
        }

        // Validate sort fields
        if (!empty($allowedFields)) {
            foreach (array_keys($this->sort) as $field) {
                if (!in_array($field, $allowedFields)) {
                    $errors[] = "Invalid sort field: {$field}";
                }
            }
        }

        // Validate relations
        if (!empty($allowedRelations)) {
            foreach ($this->with as $relation) {
                // Extract relation name (before colon if field selection is used)
                $relationName = str_contains($relation, ':') ? explode(':', $relation)[0] : $relation;
                if (!in_array($relationName, $allowedRelations)) {
                    $errors[] = "Invalid relation: {$relationName}";
                }
            }
        }

        return $errors;
    }
}
