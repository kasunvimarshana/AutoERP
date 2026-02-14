<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\DTOs\QueryConfig;

/**
 * QueryConfig Unit Tests
 * 
 * Tests for the QueryConfig DTO functionality
 */
class QueryConfigTest extends TestCase
{

    /**
     * Test QueryConfig creation from request parameters
     */
    public function test_query_config_from_request_basic()
    {
        $params = [
            'select' => 'id,name,email',
            'per_page' => 25,
            'page' => 2,
            'paginate' => true,
        ];

        $config = QueryConfig::fromRequest($params);

        $this->assertEquals(['id', 'name', 'email'], $config->select);
        $this->assertEquals(25, $config->perPage);
        $this->assertEquals(2, $config->page);
        $this->assertTrue($config->paginate);
    }

    /**
     * Test search configuration
     */
    public function test_query_config_search()
    {
        $params = [
            'search' => 'test query',
            'search_fields' => 'name,description,email',
        ];

        $config = QueryConfig::fromRequest($params);

        $this->assertNotNull($config->search);
        $this->assertEquals('test query', $config->search['query']);
        $this->assertEquals(['name', 'description', 'email'], $config->search['fields']);
    }

    /**
     * Test filter configuration
     */
    public function test_query_config_filters()
    {
        $params = [
            'filters' => [
                'status' => 'active',
                'age' => ['operator' => '>', 'value' => 18],
            ],
        ];

        $config = QueryConfig::fromRequest($params);

        $this->assertEquals('active', $config->filters['status']);
        $this->assertEquals('>', $config->filters['age']['operator']);
        $this->assertEquals(18, $config->filters['age']['value']);
    }

    /**
     * Test sort configuration
     */
    public function test_query_config_sorting()
    {
        $params = [
            'sort' => 'name:asc,created_at:desc',
        ];

        $config = QueryConfig::fromRequest($params);

        $this->assertEquals('asc', $config->sort['name']);
        $this->assertEquals('desc', $config->sort['created_at']);
    }

    /**
     * Test eager loading configuration
     */
    public function test_query_config_eager_loading()
    {
        $params = [
            'with' => 'profile,posts:id,title',
            'with_count' => 'comments,likes',
        ];

        $config = QueryConfig::fromRequest($params);

        $this->assertContains('profile', $config->with);
        $this->assertContains('posts:id,title', $config->with);
        $this->assertContains('comments', $config->withCount);
        $this->assertContains('likes', $config->withCount);
    }

    /**
     * Test relation filters
     */
    public function test_query_config_relation_filters()
    {
        $params = [
            'relation_filters' => [
                'user.status' => 'active',
                'category.name' => 'Electronics',
            ],
        ];

        $config = QueryConfig::fromRequest($params);

        $this->assertEquals('active', $config->relationFilters['user.status']);
        $this->assertEquals('Electronics', $config->relationFilters['category.name']);
    }

    /**
     * Test validation with allowed fields
     */
    public function test_query_config_validation_allowed_fields()
    {
        $config = new QueryConfig();
        $config->select = ['id', 'name', 'invalid_field'];
        $config->filters = ['status' => 'active', 'invalid_filter' => 'value'];

        $allowedFields = ['id', 'name', 'status', 'created_at'];
        $errors = $config->validate($allowedFields, []);

        $this->assertNotEmpty($errors);
        $this->assertContains('Invalid select field: invalid_field', $errors);
        $this->assertContains('Invalid filter field: invalid_filter', $errors);
    }

    /**
     * Test validation with allowed relations
     */
    public function test_query_config_validation_allowed_relations()
    {
        $config = new QueryConfig();
        $config->with = ['profile', 'invalid_relation'];

        $allowedRelations = ['profile', 'posts', 'comments'];
        $errors = $config->validate([], $allowedRelations);

        $this->assertNotEmpty($errors);
        $this->assertContains('Invalid relation: invalid_relation', $errors);
    }

    /**
     * Test validation passes with valid configuration
     */
    public function test_query_config_validation_passes()
    {
        $config = new QueryConfig();
        $config->select = ['id', 'name', 'status'];
        $config->filters = ['status' => 'active'];
        $config->sort = ['name' => 'asc'];
        $config->with = ['profile', 'posts'];

        $allowedFields = ['id', 'name', 'status', 'created_at'];
        $allowedRelations = ['profile', 'posts', 'comments'];
        $errors = $config->validate($allowedFields, $allowedRelations);

        $this->assertEmpty($errors);
    }

    /**
     * Test tenant ID configuration
     */
    public function test_query_config_tenant_id()
    {
        $params = [
            'tenant_id' => 123,
        ];

        $config = QueryConfig::fromRequest($params);

        $this->assertEquals(123, $config->tenantId);
    }

    /**
     * Test disable pagination
     */
    public function test_query_config_disable_pagination()
    {
        $params = [
            'paginate' => 'false',
        ];

        $config = QueryConfig::fromRequest($params);

        $this->assertFalse($config->paginate);
    }

    /**
     * Test array format for select
     */
    public function test_query_config_select_array_format()
    {
        $params = [
            'select' => ['id', 'name', 'email'],
        ];

        $config = QueryConfig::fromRequest($params);

        $this->assertEquals(['id', 'name', 'email'], $config->select);
    }

    /**
     * Test default values
     */
    public function test_query_config_defaults()
    {
        $config = new QueryConfig();

        $this->assertEquals(['*'], $config->select);
        $this->assertNull($config->search);
        $this->assertEquals([], $config->filters);
        $this->assertEquals([], $config->sort);
        $this->assertNull($config->perPage);
        $this->assertEquals(1, $config->page);
        $this->assertEquals([], $config->with);
        $this->assertTrue($config->paginate);
        $this->assertNull($config->tenantId);
    }
}
