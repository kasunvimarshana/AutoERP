# Enhanced Metadata-Driven UI Components Documentation

## Overview

This document provides comprehensive documentation for the enhanced metadata-driven UI components added to the AutoERP platform. These components enable fully dynamic, runtime-configurable user interfaces based on metadata definitions.

## Table of Contents

1. [Advanced Field Components](#advanced-field-components)
2. [Dynamic Dashboard](#dynamic-dashboard)
3. [Accessibility Features](#accessibility-features)
4. [Validation System](#validation-system)
5. [Internationalization](#internationalization)
6. [Theming System](#theming-system)
7. [Performance Utilities](#performance-utilities)
8. [Usage Examples](#usage-examples)

---

## Advanced Field Components

### RichTextEditor

A WYSIWYG rich text editor with formatting capabilities.

**Location**: `frontend/src/components/fields/RichTextEditor.vue`

**Props**:
- `modelValue` (String): The HTML content
- `label` (String): Field label
- `placeholder` (String): Placeholder text
- `required` (Boolean): Whether the field is required
- `disabled` (Boolean): Whether the field is disabled
- `maxLength` (Number): Maximum character count
- `showCharCount` (Boolean): Show character counter
- `toolbar` (Array): Toolbar buttons to show
- `error` (String): Error message
- `help` (String): Help text

**Events**:
- `update:modelValue`: Emitted when content changes
- `focus`: Emitted when editor gains focus
- `blur`: Emitted when editor loses focus

**Usage**:
```vue
<RichTextEditor
  v-model="content"
  label="Description"
  :toolbar="['bold', 'italic', 'underline', 'list', 'link']"
  placeholder="Enter description..."
  :show-char-count="true"
/>
```

**Metadata Configuration**:
```json
{
  "name": "description",
  "label": "Description",
  "type": "richtext",
  "required": false,
  "ui_config": {
    "placeholder": "Enter description...",
    "toolbar": ["bold", "italic", "underline", "list", "link"],
    "help": "Provide a detailed description"
  }
}
```

---

### FileUpload

File upload component with drag-and-drop support.

**Location**: `frontend/src/components/fields/FileUpload.vue`

**Props**:
- `modelValue` (Array|File|null): Selected files
- `label` (String): Field label
- `accept` (String): Accepted file types (e.g., "image/*")
- `multiple` (Boolean): Allow multiple file selection
- `required` (Boolean): Whether the field is required
- `disabled` (Boolean): Whether the field is disabled
- `maxSize` (Number): Maximum file size in bytes (default: 10MB)
- `error` (String): Error message
- `help` (String): Help text

**Events**:
- `update:modelValue`: Emitted when files change
- `change`: Emitted when files change

**Usage**:
```vue
<FileUpload
  v-model="files"
  label="Attachments"
  accept="image/*,.pdf"
  :multiple="true"
  :max-size="5 * 1024 * 1024"
/>
```

**Metadata Configuration**:
```json
{
  "name": "attachments",
  "label": "Attachments",
  "type": "file",
  "required": false,
  "ui_config": {
    "accept": "image/*,.pdf",
    "multiple": true,
    "maxSize": 5242880,
    "help": "Upload images or PDF files (max 5MB)"
  }
}
```

---

### MultiSelect

Multi-selection dropdown with search and keyboard navigation.

**Location**: `frontend/src/components/fields/MultiSelect.vue`

**Props**:
- `modelValue` (Array): Selected values
- `options` (Array): Available options `[{ value, label }]`
- `label` (String): Field label
- `placeholder` (String): Placeholder text
- `required` (Boolean): Whether the field is required
- `disabled` (Boolean): Whether the field is disabled
- `searchable` (Boolean): Enable search functionality
- `error` (String): Error message
- `help` (String): Help text

**Events**:
- `update:modelValue`: Emitted when selection changes
- `change`: Emitted when selection changes

**Usage**:
```vue
<MultiSelect
  v-model="selectedCategories"
  :options="categoryOptions"
  label="Categories"
  placeholder="Select categories..."
  :searchable="true"
/>
```

**Metadata Configuration**:
```json
{
  "name": "categories",
  "label": "Categories",
  "type": "multiselect",
  "required": false,
  "options": [
    { "value": "electronics", "label": "Electronics" },
    { "value": "clothing", "label": "Clothing" },
    { "value": "food", "label": "Food" }
  ],
  "ui_config": {
    "placeholder": "Select categories...",
    "searchable": true,
    "help": "Select one or more categories"
  }
}
```

---

## Dynamic Dashboard

A fully configurable dashboard component that supports multiple widget types.

**Location**: `frontend/src/components/common/DynamicDashboard.vue`

**Props**:
- `config` (Object): Dashboard configuration
  - `title` (String): Dashboard title
  - `columns` (Number): Grid columns (default: 12)
  - `gap` (String): Grid gap (default: '1rem')
- `widgets` (Array): Array of widget definitions
- `loading` (Boolean): Loading state
- `autoRefresh` (Number): Auto-refresh interval in seconds (0 = disabled)

**Widget Types**:

### 1. KPI Widget
Displays a key performance indicator with value and change indicator.

```javascript
{
  id: 'total-sales',
  type: 'kpi',
  title: 'Total Sales',
  col_span: 3,
  data: {
    value: 125000,
    label: 'This Month',
    change: 12.5 // positive = increase, negative = decrease
  },
  format: 'currency',
  currency: 'USD'
}
```

### 2. Chart Widget
Container for chart visualizations (requires integration with charting library).

```javascript
{
  id: 'sales-trend',
  type: 'chart',
  title: 'Sales Trend',
  col_span: 6,
  chart_type: 'line',
  data: { /* chart data */ }
}
```

### 3. Table Widget
Displays data in a table using the DynamicTable component.

```javascript
{
  id: 'recent-orders',
  type: 'table',
  title: 'Recent Orders',
  col_span: 6,
  data: {
    fields: [
      { name: 'order_id', label: 'Order ID', is_visible_list: true },
      { name: 'customer', label: 'Customer', is_visible_list: true },
      { name: 'total', label: 'Total', is_visible_list: true }
    ],
    items: [ /* order data */ ]
  },
  config: {
    paginated: true,
    perPage: 5,
    showRowActions: false
  }
}
```

### 4. List Widget
Displays a simple list of items.

```javascript
{
  id: 'top-products',
  type: 'list',
  title: 'Top Products',
  col_span: 3,
  data: {
    items: [
      { label: 'Product A', value: 150 },
      { label: 'Product B', value: 120 },
      { label: 'Product C', value: 95 }
    ]
  }
}
```

**Usage Example**:
```vue
<template>
  <DynamicDashboard
    :config="dashboardConfig"
    :widgets="widgets"
    :loading="loading"
    :auto-refresh="300"
    @widget-refresh="handleWidgetRefresh"
  >
    <!-- Custom widget slot -->
    <template #widget-sales-chart="{ widget, data }">
      <MyCustomChart :data="data" />
    </template>
  </DynamicDashboard>
</template>

<script setup>
import { ref } from 'vue';
import DynamicDashboard from '@/components/common/DynamicDashboard.vue';

const dashboardConfig = ref({
  title: 'Sales Dashboard',
  columns: 12,
  gap: '1rem'
});

const widgets = ref([
  // KPI widgets
  {
    id: 'total-revenue',
    type: 'kpi',
    title: 'Total Revenue',
    col_span: 3,
    data: {
      value: 125000,
      change: 12.5
    },
    format: 'currency'
  },
  // More widgets...
]);

const handleWidgetRefresh = (widgetId) => {
  // Refresh widget data
  console.log('Refreshing widget:', widgetId);
};
</script>
```

---

## Accessibility Features

### useAccessibility Composable

**Location**: `frontend/src/composables/useAccessibility.js`

Provides accessibility utilities for screen readers, focus management, and keyboard navigation.

**Methods**:

#### `announce(message, priority)`
Announces a message to screen readers.

```javascript
const { announce } = useAccessibility();

// Polite announcement (doesn't interrupt)
announce('Product saved successfully');

// Assertive announcement (interrupts current reading)
announce('Error occurred', 'assertive');
```

#### `setupFocusTrap(element)`
Creates a focus trap for modals and dialogs.

```javascript
const { setupFocusTrap, removeFocusTrap } = useAccessibility();

const cleanup = setupFocusTrap(modalElement);

// Later, when closing modal
cleanup();
```

#### `setupKeyboardNav(container, options)`
Sets up keyboard navigation for lists.

```javascript
const { setupKeyboardNav } = useAccessibility();

const cleanup = setupKeyboardNav(listContainer, {
  itemSelector: '[role="option"]',
  onSelect: (item, index) => {
    console.log('Selected:', item);
  },
  orientation: 'vertical' // or 'horizontal'
});
```

#### `generateId(prefix)`
Generates unique IDs for ARIA attributes.

```javascript
const { generateId } = useAccessibility();

const labelId = generateId('label'); // 'label-abc123def'
```

---

### AccessibleModal Component

**Location**: `frontend/src/components/common/AccessibleModal.vue`

A fully accessible modal dialog with ARIA support.

**Props**:
- `modelValue` (Boolean): Show/hide modal
- `title` (String): Modal title
- `size` (String): Modal size ('sm', 'md', 'lg', 'xl', 'full')
- `closeOnOverlay` (Boolean): Close when clicking overlay
- `closeOnEscape` (Boolean): Close when pressing Escape
- `showActions` (Boolean): Show action buttons
- `showCancel` (Boolean): Show cancel button
- `showConfirm` (Boolean): Show confirm button
- `cancelLabel` (String): Cancel button text
- `confirmLabel` (String): Confirm button text
- `confirmDisabled` (Boolean): Disable confirm button

**Events**:
- `update:modelValue`: Emitted when modal visibility changes
- `close`: Emitted when modal closes
- `cancel`: Emitted when cancel button clicked
- `confirm`: Emitted when confirm button clicked

**Usage**:
```vue
<template>
  <AccessibleModal
    v-model="showModal"
    title="Confirm Action"
    size="md"
    :show-actions="true"
    confirm-label="Delete"
    @confirm="handleDelete"
    @cancel="showModal = false"
  >
    <p>Are you sure you want to delete this item?</p>
  </AccessibleModal>
</template>
```

---

## Validation System

### useValidation Composable

**Location**: `frontend/src/composables/useValidation.js`

Provides metadata-driven form validation.

**Methods**:

#### `validateField(field, value)`
Validates a single field based on metadata rules.

```javascript
const { validateField } = useValidation();

const field = {
  name: 'email',
  label: 'Email',
  type: 'email',
  required: true,
  validation: ['email', 'max:255']
};

const error = validateField(field, 'invalid-email');
// Returns: "Email must be a valid email address"
```

#### `validateAll(fields, formData)`
Validates all fields at once.

```javascript
const { validateAll, errors } = useValidation();

const isValid = validateAll(fields, formData);

if (!isValid) {
  console.log('Errors:', errors.value);
}
```

**Supported Validation Rules**:

- `required`: Field is required
- `email`: Valid email format
- `url`: Valid URL format
- `min:n`: Minimum length/value
- `max:n`: Maximum length/value
- `pattern:regex`: Custom regex pattern
- `alpha`: Only letters
- `alphanumeric`: Letters and numbers only
- `numeric`: Only numbers
- Custom functions: `(value, field) => error || null`

**Usage with DynamicForm**:
```vue
<script setup>
import { ref } from 'vue';
import { useValidation } from '@/composables/useValidation';
import DynamicForm from '@/components/common/DynamicForm.vue';

const { validateAll, errors } = useValidation();

const fields = ref([
  {
    name: 'email',
    label: 'Email',
    type: 'email',
    required: true,
    validation: ['email', 'max:255']
  }
]);

const formData = ref({});

const handleSubmit = () => {
  if (validateAll(fields.value, formData.value)) {
    // Form is valid
    console.log('Submitting:', formData.value);
  }
};
</script>
```

---

## Internationalization

### useI18n Composable

**Location**: `frontend/src/composables/useI18n.js`

Provides multi-language support with formatting utilities.

**Methods**:

#### `t(key, params)`
Translates a key with optional parameter substitution.

```javascript
const { t } = useI18n();

// Simple translation
t('common.save'); // "Save"

// With parameters
t('validation.min', { field: 'Name', min: 3 });
// "Name must be at least 3 characters"
```

#### `n(number, options)`
Formats numbers according to locale.

```javascript
const { n } = useI18n();

n(1234.56); // "1,234.56" (en-US)
n(1234.56, { minimumFractionDigits: 2 }); // "1,234.56"
```

#### `c(amount, currency)`
Formats currency.

```javascript
const { c } = useI18n();

c(1234.56, 'USD'); // "$1,234.56"
c(1234.56, 'EUR'); // "€1,234.56"
```

#### `d(date, options)`
Formats dates.

```javascript
const { d } = useI18n();

d(new Date()); // "February 4, 2026"
d(new Date(), { dateStyle: 'short' }); // "2/4/26"
```

#### `rt(date)`
Formats relative time.

```javascript
const { rt } = useI18n();

rt(new Date(Date.now() - 3600000)); // "1 hour ago"
rt(new Date(Date.now() + 86400000)); // "in 1 day"
```

#### `setLocale(locale)`
Changes the current locale.

```javascript
const { setLocale } = useI18n();

await setLocale('es'); // Switch to Spanish
```

**Usage**:
```vue
<template>
  <div>
    <h1>{{ t('dashboard.title') }}</h1>
    <p>{{ t('dashboard.sales', { amount: c(totalSales, 'USD') }) }}</p>
    <p>{{ t('dashboard.updated') }}: {{ rt(lastUpdated) }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from '@/composables/useI18n';

const { t, c, rt } = useI18n();

const totalSales = ref(125000);
const lastUpdated = ref(new Date());
</script>
```

---

## Theming System

### useTheme Composable

**Location**: `frontend/src/composables/useTheme.js`

Provides tenant-specific theming with CSS variable injection.

**Methods**:

#### `setTheme(theme)`
Applies a theme configuration.

```javascript
const { setTheme } = useTheme();

setTheme({
  name: 'custom',
  colors: {
    primary: '#3b82f6',
    secondary: '#6b7280',
    success: '#10b981',
    danger: '#ef4444'
  },
  fonts: {
    sans: 'Inter, system-ui, sans-serif'
  },
  spacing: {
    unit: '0.25rem'
  }
});
```

#### `toggleDarkMode()`
Toggles between light and dark mode.

```javascript
const { toggleDarkMode, isDark } = useTheme();

toggleDarkMode();
console.log('Dark mode:', isDark.value);
```

#### `getColor(colorName)`
Gets a color value by name.

```javascript
const { getColor } = useTheme();

const primaryColor = getColor('primary'); // '#3b82f6'
```

**Usage**:
```vue
<template>
  <div>
    <button @click="toggleDarkMode">
      {{ isDark ? 'Light Mode' : 'Dark Mode' }}
    </button>
  </div>
</template>

<script setup>
import { useTheme } from '@/composables/useTheme';

const { toggleDarkMode, isDark } = useTheme();
</script>
```

---

## Performance Utilities

### Lazy Loading

**Location**: `frontend/src/utils/lazyLoad.js`

Utilities for code splitting and lazy loading.

#### `lazyLoad(importFunc, options)`
Creates a lazy-loaded component.

```javascript
import { lazyLoad } from '@/utils/lazyLoad';

const MyComponent = lazyLoad(
  () => import('./MyComponent.vue'),
  {
    delay: 200,
    timeout: 30000
  }
);
```

#### `debounce(fn, delay)`
Debounces a function.

```javascript
import { debounce } from '@/utils/lazyLoad';

const handleSearch = debounce((query) => {
  console.log('Searching:', query);
}, 300);
```

#### `throttle(fn, limit)`
Throttles a function.

```javascript
import { throttle } from '@/utils/lazyLoad';

const handleScroll = throttle(() => {
  console.log('Scrolling...');
}, 100);
```

#### `memoize(fn)`
Memoizes expensive computations.

```javascript
import { memoize } from '@/utils/lazyLoad';

const expensiveCalculation = memoize((a, b) => {
  // Expensive operation
  return a * b * Math.random();
});

// First call: computed
expensiveCalculation(5, 10);

// Second call with same args: cached
expensiveCalculation(5, 10);
```

---

## Usage Examples

### Complete CRUD View with Metadata

```vue
<template>
  <div class="entity-view">
    <!-- Dashboard -->
    <DynamicDashboard
      v-if="showDashboard"
      :config="dashboardConfig"
      :widgets="dashboardWidgets"
      @widget-refresh="refreshWidget"
    />

    <!-- Table View -->
    <DynamicTable
      v-if="!showForm"
      :fields="listFields"
      :data="items"
      :loading="loading"
      @create="handleCreate"
      @edit="handleEdit"
      @delete="handleDelete"
    />

    <!-- Form Modal -->
    <AccessibleModal
      v-model="showForm"
      :title="formTitle"
      size="lg"
      @close="handleClose"
    >
      <DynamicForm
        :fields="formFields"
        v-model="formData"
        @submit="handleSubmit"
        @cancel="handleClose"
      />
    </AccessibleModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useMetadataStore } from '@/stores/metadataStore';
import { useValidation } from '@/composables/useValidation';
import { useI18n } from '@/composables/useI18n';
import { useAccessibility } from '@/composables/useAccessibility';
import DynamicDashboard from '@/components/common/DynamicDashboard.vue';
import DynamicTable from '@/components/common/DynamicTable.vue';
import DynamicForm from '@/components/common/DynamicForm.vue';
import AccessibleModal from '@/components/common/AccessibleModal.vue';

const props = defineProps({
  entityName: {
    type: String,
    required: true
  }
});

const metadataStore = useMetadataStore();
const { validateAll } = useValidation();
const { t } = useI18n();
const { announce } = useAccessibility();

const entity = ref(null);
const listFields = ref([]);
const formFields = ref([]);
const items = ref([]);
const formData = ref({});
const showForm = ref(false);
const showDashboard = ref(true);
const loading = ref(false);
const editingId = ref(null);

const formTitle = computed(() => {
  return editingId.value
    ? t('common.edit') + ' ' + entity.value?.label_singular
    : t('common.create') + ' ' + entity.value?.label_singular;
});

const dashboardConfig = ref({
  title: 'Dashboard',
  columns: 12,
  gap: '1rem'
});

const dashboardWidgets = ref([
  {
    id: 'total-count',
    type: 'kpi',
    title: 'Total Items',
    col_span: 3,
    data: {
      value: 0,
      change: 0
    }
  }
]);

onMounted(async () => {
  // Fetch entity metadata
  entity.value = await metadataStore.fetchEntity(props.entityName);
  
  // Fetch field configurations
  listFields.value = await metadataStore.fetchFieldConfig(props.entityName, 'list');
  formFields.value = await metadataStore.fetchFieldConfig(props.entityName, 'form');
  
  // Load data
  await loadData();
});

const loadData = async () => {
  loading.value = true;
  try {
    // Call your API service to fetch items
    // items.value = await yourService.getAll();
    
    // Update dashboard
    dashboardWidgets.value[0].data.value = items.value.length;
  } finally {
    loading.value = false;
  }
};

const handleCreate = () => {
  formData.value = {};
  editingId.value = null;
  showForm.value = true;
};

const handleEdit = (item) => {
  formData.value = { ...item };
  editingId.value = item.id;
  showForm.value = true;
};

const handleClose = () => {
  showForm.value = false;
  formData.value = {};
  editingId.value = null;
};

const handleSubmit = async () => {
  if (!validateAll(formFields.value, formData.value)) {
    announce('Please fix validation errors', 'assertive');
    return;
  }

  try {
    if (editingId.value) {
      // Update existing item
      // await yourService.update(editingId.value, formData.value);
      announce(t('common.updated_successfully'));
    } else {
      // Create new item
      // await yourService.create(formData.value);
      announce(t('common.created_successfully'));
    }
    
    handleClose();
    await loadData();
  } catch (error) {
    announce(t('common.error'), 'assertive');
  }
};

const handleDelete = async (item) => {
  if (confirm(t('common.confirm_delete'))) {
    try {
      // await yourService.delete(item.id);
      announce(t('common.deleted_successfully'));
      await loadData();
    } catch (error) {
      announce(t('common.error'), 'assertive');
    }
  }
};

const refreshWidget = async (widgetId) => {
  // Refresh widget data
  await loadData();
};
</script>
```

---

## Best Practices

1. **Always use metadata definitions** for field configuration
2. **Leverage composables** for reusable logic
3. **Ensure accessibility** by using provided utilities
4. **Validate user input** using the validation composable
5. **Internationalize all text** using the i18n composable
6. **Apply theming** consistently across components
7. **Optimize performance** with lazy loading and memoization
8. **Test thoroughly** with different locales and themes

---

## Browser Support

All components support:
- Chrome/Edge (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- Mobile browsers (iOS Safari, Chrome Android)

Accessibility features follow WCAG 2.1 AA standards.
