# DynamicTable Component

## Overview

The DynamicTable component is a metadata-driven table component that renders tables dynamically based on TableMetadata configuration. It supports sorting, filtering, pagination, row actions, bulk actions, and data export.

## Features

- ✅ Fetch data from any API endpoint
- ✅ Render columns dynamically based on metadata
- ✅ Sorting support (per column configuration)
- ✅ Advanced filtering (search + multiple filter types)
- ✅ Pagination with configurable page sizes
- ✅ Row actions (edit, delete, custom actions)
- ✅ Bulk actions with row selection
- ✅ Multiple column types: text, number, date, datetime, boolean, badge, custom
- ✅ Formatters for currency, dates, percentages, etc.
- ✅ Custom component support for columns
- ✅ Loading and error states
- ✅ Export functionality (CSV)
- ✅ Responsive design with Tailwind CSS

## Usage

```vue
<template>
  <DynamicTable
    :metadata="tableMetadata"
    @action="handleRowAction"
    @bulkAction="handleBulkAction"
    @loaded="onDataLoaded"
    @failed="onError"
  />
</template>

<script setup lang="ts">
import { ref } from 'vue';
import DynamicTable from '@/components/tables/DynamicTable.vue';
import type { TableMetadata } from '@/types/metadata';

const tableMetadata = ref<TableMetadata>({
  id: 'users-table',
  title: 'Users Management',
  apiEndpoint: '/api/users',
  searchable: true,
  sortable: true,
  exportable: true,
  columns: [
    {
      key: 'id',
      label: 'ID',
      type: 'number',
      sortable: true,
      width: '80px'
    },
    {
      key: 'name',
      label: 'Full Name',
      type: 'text',
      sortable: true
    },
    {
      key: 'email',
      label: 'Email Address',
      type: 'text',
      sortable: true
    },
    {
      key: 'status',
      label: 'Status',
      type: 'badge',
      sortable: true
    },
    {
      key: 'created_at',
      label: 'Created',
      type: 'datetime',
      sortable: true,
      formatter: 'medium'
    },
    {
      key: 'balance',
      label: 'Balance',
      type: 'number',
      formatter: 'currency',
      align: 'right'
    }
  ],
  actions: [
    {
      id: 'edit',
      label: 'Edit',
      icon: 'edit',
      variant: 'primary',
      action: 'edit'
    },
    {
      id: 'delete',
      label: 'Delete',
      icon: 'trash',
      variant: 'danger',
      action: 'delete',
      confirm: {
        title: 'Delete User',
        message: 'Are you sure you want to delete this user?'
      }
    }
  ],
  bulkActions: [
    {
      id: 'bulk-delete',
      label: 'Delete Selected',
      icon: 'trash',
      action: 'bulk-delete',
      confirm: {
        title: 'Delete Multiple Users',
        message: 'Are you sure you want to delete the selected users?'
      }
    },
    {
      id: 'bulk-export',
      label: 'Export Selected',
      icon: 'download',
      action: 'bulk-export'
    }
  ],
  filters: [
    {
      name: 'status',
      label: 'Status',
      type: 'select',
      options: [
        { label: 'Active', value: 'active' },
        { label: 'Inactive', value: 'inactive' },
        { label: 'Pending', value: 'pending' }
      ]
    },
    {
      name: 'created_date',
      label: 'Created Date',
      type: 'daterange'
    }
  ],
  pagination: {
    enabled: true,
    pageSize: 25,
    pageSizeOptions: [10, 25, 50, 100]
  }
});

function handleRowAction(actionType: string, rowData: any) {
  console.log('Row action:', actionType, rowData);
  
  if (actionType === 'edit') {
    // Handle edit action
  } else if (actionType === 'delete') {
    // Handle delete action
  }
}

function handleBulkAction(actionType: string, selectedRows: any[]) {
  console.log('Bulk action:', actionType, selectedRows);
  
  if (actionType === 'bulk-delete') {
    // Handle bulk delete
  } else if (actionType === 'bulk-export') {
    // Handle bulk export
  }
}

function onDataLoaded(records: any[]) {
  console.log('Data loaded:', records.length, 'records');
}

function onError(message: string) {
  console.error('Table error:', message);
}
</script>
```

## Column Types

### Text
```typescript
{
  key: 'name',
  label: 'Name',
  type: 'text',
  formatter: 'uppercase' // or 'lowercase', 'capitalize', 'truncate'
}
```

### Number
```typescript
{
  key: 'amount',
  label: 'Amount',
  type: 'number',
  formatter: 'currency' // or 'percentage', 'decimal', 'integer'
}
```

### Date
```typescript
{
  key: 'date',
  label: 'Date',
  type: 'date',
  formatter: 'medium' // or 'short', 'long', 'full'
}
```

### DateTime
```typescript
{
  key: 'timestamp',
  label: 'Timestamp',
  type: 'datetime',
  formatter: 'short' // or 'medium', 'long', 'time', 'full'
}
```

### Boolean
```typescript
{
  key: 'active',
  label: 'Active',
  type: 'boolean'
}
```

### Badge
```typescript
{
  key: 'status',
  label: 'Status',
  type: 'badge' // Auto-colors based on value
}
```

### Custom
```typescript
{
  key: 'custom_data',
  label: 'Custom',
  type: 'custom',
  customComponent: 'MyCustomCell'
}
```

## Filter Types

- **text**: Text input filter
- **select**: Dropdown select filter
- **multiselect**: Multiple selection filter
- **date**: Single date picker
- **daterange**: Date range picker (start and end)
- **number**: Number input filter
- **boolean**: Yes/No filter

## API Response Format

The component expects responses in one of these formats:

### Simple Array
```json
{
  "data": [
    { "id": 1, "name": "John" },
    { "id": 2, "name": "Jane" }
  ]
}
```

### Paginated Response
```json
{
  "data": [
    { "id": 1, "name": "John" }
  ],
  "meta": {
    "current_page": 1,
    "total": 100,
    "per_page": 25
  }
}
```

## Query Parameters

The component automatically sends these query parameters to the API:

- `page`: Current page number
- `per_page`: Items per page
- `search`: Search term
- `sort_by`: Column to sort by
- `sort_order`: 'asc' or 'desc'
- `filter[field_name]`: Filter values

## Exposed Methods

```typescript
// Refresh table data
tableRef.value.reload();

// Clear selected rows
tableRef.value.clearChosenRows();

// Get selected row data
const selected = tableRef.value.getChosenRecords();
```

## Custom Cell Components

Create custom cell components in `/components/custom/`:

```vue
<template>
  <div>{{ customFormatting }}</div>
</template>

<script setup lang="ts">
import type { TableColumnMetadata } from '@/types/metadata';

interface Props {
  descriptor: TableColumnMetadata;
  record: any;
  rawValue: any;
}

const props = defineProps<Props>();

const customFormatting = computed(() => {
  // Your custom logic
  return props.rawValue;
});
</script>
```

## Architecture

The DynamicTable component follows a modular architecture:

```
tables/
├── DynamicTable.vue          # Main table component
├── cells/                     # Cell renderers for different types
│   ├── TextCell.vue
│   ├── NumberCell.vue
│   ├── DateCell.vue
│   ├── DateTimeCell.vue
│   ├── BooleanCell.vue
│   └── BadgeCell.vue
└── filters/                   # Filter components
    ├── TextFilter.vue
    ├── SelectFilter.vue
    ├── MultiSelectFilter.vue
    ├── DateFilter.vue
    ├── DateRangeFilter.vue
    ├── NumberFilter.vue
    └── BooleanFilter.vue
```

## Styling

The component uses Tailwind CSS classes and follows the AutoERP design system. All colors use indigo as the primary color, which matches the application theme.

## Performance

- Debounced search (400ms)
- Lazy-loaded cell and filter components
- Efficient row selection with Set
- Minimal re-renders with computed properties
