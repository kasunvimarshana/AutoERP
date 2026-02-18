<?php

namespace Database\Seeders;

use App\Models\MetadataEntity;
use App\Models\MetadataField;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ComprehensiveMetadataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // ==========================================
        // PRODUCT ENTITY - Comprehensive Example
        // ==========================================
        $productEntity = MetadataEntity::updateOrCreate(
            ['name' => 'product'],
            [
                'label' => 'Product',
                'label_plural' => 'Products',
                'table_name' => 'products',
                'icon' => 'package',
                'module' => 'inventory',
                'description' => 'Products and inventory items',
                'is_system' => false,
                'is_active' => true,
                'has_workflow' => true,
                'has_audit_trail' => true,
                'is_tenant_scoped' => true,
                'ui_config' => [
                    'list_page_size' => 20,
                    'enable_bulk_actions' => true,
                    'enable_export' => true,
                    'enable_import' => true,
                    'default_sort' => 'name',
                    'default_sort_direction' => 'asc',
                ],
                'api_config' => [
                    'base_path' => '/api/v1/inventory/products',
                    'supports_pagination' => true,
                    'supports_search' => true,
                    'supports_filtering' => true,
                ],
                'permissions' => [
                    'view' => 'products.view',
                    'create' => 'products.create',
                    'edit' => 'products.edit',
                    'delete' => 'products.delete',
                ],
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // Product Fields
        $productFields = [
            [
                'name' => 'code',
                'label' => 'Product Code',
                'type' => 'text',
                'column_name' => 'code',
                'description' => 'Unique product identifier',
                'is_required' => true,
                'is_unique' => true,
                'is_searchable' => true,
                'is_filterable' => true,
                'is_sortable' => true,
                'is_visible_list' => true,
                'is_visible_detail' => true,
                'is_visible_form' => true,
                'is_readonly' => false,
                'is_system' => false,
                'order' => 1,
                'validation_rules' => ['required', 'string', 'max:50', 'unique:products,code'],
                'ui_config' => [
                    'placeholder' => 'Enter product code',
                    'help' => 'Unique code for product identification',
                    'width' => '150px',
                ],
            ],
            [
                'name' => 'name',
                'label' => 'Product Name',
                'type' => 'text',
                'column_name' => 'name',
                'description' => 'Display name of the product',
                'is_required' => true,
                'is_unique' => false,
                'is_searchable' => true,
                'is_filterable' => true,
                'is_sortable' => true,
                'is_visible_list' => true,
                'is_visible_detail' => true,
                'is_visible_form' => true,
                'is_readonly' => false,
                'is_system' => false,
                'order' => 2,
                'validation_rules' => ['required', 'string', 'max:255'],
                'ui_config' => [
                    'placeholder' => 'Enter product name',
                    'help' => 'Name as displayed to customers',
                    'width' => '250px',
                ],
            ],
            [
                'name' => 'type',
                'label' => 'Product Type',
                'type' => 'select',
                'column_name' => 'type',
                'description' => 'Type of product',
                'is_required' => true,
                'is_unique' => false,
                'is_searchable' => false,
                'is_filterable' => true,
                'is_sortable' => true,
                'is_visible_list' => true,
                'is_visible_detail' => true,
                'is_visible_form' => true,
                'is_readonly' => false,
                'is_system' => false,
                'order' => 3,
                'validation_rules' => ['required', 'in:inventory,service,combo,bundle'],
                'options' => [
                    ['value' => 'inventory', 'label' => 'Inventory Item'],
                    ['value' => 'service', 'label' => 'Service'],
                    ['value' => 'combo', 'label' => 'Combo Product'],
                    ['value' => 'bundle', 'label' => 'Bundle'],
                ],
                'ui_config' => [
                    'default_value' => 'inventory',
                    'width' => '150px',
                ],
            ],
            [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'textarea',
                'column_name' => 'description',
                'description' => 'Detailed product description',
                'is_required' => false,
                'is_unique' => false,
                'is_searchable' => true,
                'is_filterable' => false,
                'is_sortable' => false,
                'is_visible_list' => false,
                'is_visible_detail' => true,
                'is_visible_form' => true,
                'is_readonly' => false,
                'is_system' => false,
                'order' => 4,
                'validation_rules' => ['nullable', 'string'],
                'ui_config' => [
                    'placeholder' => 'Enter product description',
                    'rows' => 4,
                ],
            ],
            [
                'name' => 'buying_price',
                'label' => 'Buying Price',
                'type' => 'number',
                'column_name' => 'buying_price',
                'description' => 'Cost of purchasing the product',
                'is_required' => false,
                'is_unique' => false,
                'is_searchable' => false,
                'is_filterable' => true,
                'is_sortable' => true,
                'is_visible_list' => true,
                'is_visible_detail' => true,
                'is_visible_form' => true,
                'is_readonly' => false,
                'is_system' => false,
                'order' => 5,
                'validation_rules' => ['nullable', 'numeric', 'min:0'],
                'ui_config' => [
                    'placeholder' => '0.00',
                    'prefix' => '$',
                    'step' => '0.01',
                    'width' => '120px',
                ],
            ],
            [
                'name' => 'selling_price',
                'label' => 'Selling Price',
                'type' => 'number',
                'column_name' => 'selling_price',
                'description' => 'Price at which product is sold',
                'is_required' => false,
                'is_unique' => false,
                'is_searchable' => false,
                'is_filterable' => true,
                'is_sortable' => true,
                'is_visible_list' => true,
                'is_visible_detail' => true,
                'is_visible_form' => true,
                'is_readonly' => false,
                'is_system' => false,
                'order' => 6,
                'validation_rules' => ['nullable', 'numeric', 'min:0'],
                'ui_config' => [
                    'placeholder' => '0.00',
                    'prefix' => '$',
                    'step' => '0.01',
                    'width' => '120px',
                ],
            ],
            [
                'name' => 'stock_quantity',
                'label' => 'Stock Quantity',
                'type' => 'number',
                'column_name' => 'stock_quantity',
                'description' => 'Current stock on hand',
                'is_required' => false,
                'is_unique' => false,
                'is_searchable' => false,
                'is_filterable' => true,
                'is_sortable' => true,
                'is_visible_list' => true,
                'is_visible_detail' => true,
                'is_visible_form' => false,
                'is_readonly' => true,
                'is_system' => true,
                'order' => 7,
                'validation_rules' => ['nullable', 'integer'],
                'ui_config' => [
                    'width' => '100px',
                    'align' => 'center',
                ],
            ],
            [
                'name' => 'reorder_level',
                'label' => 'Reorder Level',
                'type' => 'number',
                'column_name' => 'reorder_level',
                'description' => 'Minimum stock before reorder alert',
                'is_required' => false,
                'is_unique' => false,
                'is_searchable' => false,
                'is_filterable' => false,
                'is_sortable' => false,
                'is_visible_list' => false,
                'is_visible_detail' => true,
                'is_visible_form' => true,
                'is_readonly' => false,
                'is_system' => false,
                'order' => 8,
                'validation_rules' => ['nullable', 'integer', 'min:0'],
                'ui_config' => [
                    'placeholder' => '0',
                    'width' => '100px',
                ],
            ],
            [
                'name' => 'is_active',
                'label' => 'Active',
                'type' => 'checkbox',
                'column_name' => 'is_active',
                'description' => 'Whether product is currently active',
                'is_required' => false,
                'is_unique' => false,
                'is_searchable' => false,
                'is_filterable' => true,
                'is_sortable' => true,
                'is_visible_list' => true,
                'is_visible_detail' => true,
                'is_visible_form' => true,
                'is_readonly' => false,
                'is_system' => false,
                'order' => 9,
                'default_value' => true,
                'validation_rules' => ['boolean'],
                'ui_config' => [
                    'width' => '80px',
                    'align' => 'center',
                ],
            ],
        ];

        foreach ($productFields as $fieldData) {
            MetadataField::updateOrCreate(
                [
                    'entity_id' => $productEntity->id,
                    'name' => $fieldData['name'],
                ],
                array_merge($fieldData, [
                    'entity_id' => $productEntity->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        // ==========================================
        // CUSTOMER ENTITY
        // ==========================================
        $customerEntity = MetadataEntity::updateOrCreate(
            ['name' => 'customer'],
            [
                'label' => 'Customer',
                'label_plural' => 'Customers',
                'table_name' => 'customers',
                'icon' => 'users',
                'module' => 'crm',
                'description' => 'Customer accounts and contacts',
                'is_system' => false,
                'is_active' => true,
                'has_workflow' => false,
                'has_audit_trail' => true,
                'is_tenant_scoped' => true,
                'ui_config' => [
                    'list_page_size' => 20,
                    'enable_bulk_actions' => true,
                    'enable_export' => true,
                    'default_sort' => 'name',
                ],
                'api_config' => [
                    'base_path' => '/api/v1/crm/customers',
                    'supports_pagination' => true,
                    'supports_search' => true,
                ],
                'permissions' => [
                    'view' => 'customers.view',
                    'create' => 'customers.create',
                    'edit' => 'customers.edit',
                    'delete' => 'customers.delete',
                ],
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // Customer Fields
        $customerFields = [
            [
                'name' => 'name',
                'label' => 'Customer Name',
                'type' => 'text',
                'column_name' => 'name',
                'is_required' => true,
                'is_searchable' => true,
                'is_visible_list' => true,
                'is_visible_form' => true,
                'order' => 1,
                'validation_rules' => ['required', 'string', 'max:255'],
            ],
            [
                'name' => 'email',
                'label' => 'Email',
                'type' => 'email',
                'column_name' => 'email',
                'is_required' => true,
                'is_searchable' => true,
                'is_visible_list' => true,
                'is_visible_form' => true,
                'order' => 2,
                'validation_rules' => ['required', 'email', 'max:255'],
            ],
            [
                'name' => 'phone',
                'label' => 'Phone',
                'type' => 'text',
                'column_name' => 'phone',
                'is_required' => false,
                'is_searchable' => true,
                'is_visible_list' => true,
                'is_visible_form' => true,
                'order' => 3,
                'validation_rules' => ['nullable', 'string', 'max:20'],
            ],
            [
                'name' => 'type',
                'label' => 'Customer Type',
                'type' => 'select',
                'column_name' => 'type',
                'is_required' => true,
                'is_filterable' => true,
                'is_visible_list' => true,
                'is_visible_form' => true,
                'order' => 4,
                'options' => [
                    ['value' => 'individual', 'label' => 'Individual'],
                    ['value' => 'business', 'label' => 'Business'],
                ],
                'validation_rules' => ['required', 'in:individual,business'],
            ],
        ];

        foreach ($customerFields as $fieldData) {
            MetadataField::updateOrCreate(
                [
                    'entity_id' => $customerEntity->id,
                    'name' => $fieldData['name'],
                ],
                array_merge($fieldData, [
                    'entity_id' => $customerEntity->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        $this->command->info('Comprehensive metadata seeded successfully!');
        $this->command->info('- Product entity with 9 fields');
        $this->command->info('- Customer entity with 4 fields');
    }
}
