<template>
  <div class="dynamic-crud-view">
    <!-- List View -->
    <div
      v-if="mode === 'list'"
      class="crud-list-view"
    >
      <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex-1">
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
            {{ entityMetadata.label }}
          </h1>
          <p
            v-if="entityMetadata.description"
            class="mt-1 text-sm text-gray-600"
          >
            {{ entityMetadata.description }}
          </p>
        </div>
        <button
          v-if="hasPermission(createPermission)"
          class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 whitespace-nowrap"
          @click="navigateToCreate"
        >
          <span class="flex items-center justify-center">
            <svg
              class="w-5 h-5 sm:mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            <span class="hidden sm:inline">Create {{ entityMetadata.singular || 'New' }}</span>
            <span class="sm:hidden">New</span>
          </span>
        </button>
      </div>

      <DynamicTable
        v-if="tableMetadata"
        :config="tableMetadata"
        :data="tableData"
        :loading="loading"
        :pagination="pagination"
        @page-change="handlePageChange"
        @sort-change="handleSortChange"
        @filter-change="handleFilterChange"
        @row-action="handleRowAction"
        @bulk-action="handleBulkAction"
      />

      <div
        v-else-if="loading"
        class="flex items-center justify-center h-64"
      >
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600" />
      </div>

      <div
        v-else
        class="text-center py-12"
      >
        <p class="text-gray-500">
          No data available
        </p>
      </div>
    </div>

    <!-- Create/Edit View -->
    <div
      v-else-if="mode === 'create' || mode === 'edit'"
      class="crud-form-view"
    >
      <div class="mb-6">
        <nav
          class="flex"
          aria-label="Breadcrumb"
        >
          <ol class="flex items-center space-x-2">
            <li>
              <a
                class="text-gray-500 hover:text-gray-700 cursor-pointer"
                @click="navigateToList"
              >
                {{ entityMetadata.label }}
              </a>
            </li>
            <li class="flex items-center">
              <svg
                class="w-5 h-5 text-gray-400"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                  clip-rule="evenodd"
                />
              </svg>
              <span class="ml-2 text-gray-700">{{ mode === 'create' ? 'Create' : 'Edit' }}</span>
            </li>
          </ol>
        </nav>
        <h1 class="mt-2 text-3xl font-bold text-gray-900">
          {{ mode === 'create' ? 'Create' : 'Edit' }} {{ entityMetadata.singular || 'Record' }}
        </h1>
      </div>

      <DynamicForm
        v-if="formMetadata"
        :metadata="formMetadata"
        :initial-data="formData"
        :loading="submitting"
        @submit="handleFormSubmit"
        @cancel="navigateToList"
      />

      <div
        v-else-if="loading"
        class="flex items-center justify-center h-64"
      >
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600" />
      </div>
    </div>

    <!-- Detail View -->
    <div
      v-else-if="mode === 'view'"
      class="crud-detail-view"
    >
      <div class="mb-6">
        <nav
          class="flex"
          aria-label="Breadcrumb"
        >
          <ol class="flex items-center space-x-2">
            <li>
              <a
                class="text-gray-500 hover:text-gray-700 cursor-pointer"
                @click="navigateToList"
              >
                {{ entityMetadata.label }}
              </a>
            </li>
            <li class="flex items-center">
              <svg
                class="w-5 h-5 text-gray-400"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                  clip-rule="evenodd"
                />
              </svg>
              <span class="ml-2 text-gray-700">Details</span>
            </li>
          </ol>
        </nav>

        <div class="mt-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
            {{ recordTitle || `${entityMetadata.singular || 'Record'} Details` }}
          </h1>
          <div class="flex flex-col sm:flex-row gap-2 sm:space-x-3">
            <button
              v-if="hasPermission(updatePermission)"
              class="px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
              @click="navigateToEdit"
            >
              Edit
            </button>
            <button
              v-if="hasPermission(deletePermission)"
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
              @click="handleDelete"
            >
              Delete
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="formData && formMetadata"
        class="bg-white shadow rounded-lg"
      >
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-medium text-gray-900">
            Information
          </h2>
        </div>
        <div class="px-6 py-4">
          <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div
              v-for="field in displayFields"
              :key="field.name"
              class="sm:col-span-1"
            >
              <dt class="text-sm font-medium text-gray-500">
                {{ field.label }}
              </dt>
              <dd class="mt-1 text-sm text-gray-900">
                {{ formatFieldValue(field, formData[field.name]) }}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <div
        v-else-if="loading"
        class="flex items-center justify-center h-64"
      >
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * DynamicCRUDView Component
 * 
 * A comprehensive, metadata-driven CRUD component that handles all CRUD operations
 * (Create, Read, Update, Delete) for any entity in the system.
 * 
 * Features:
 * - List view with pagination, sorting, and filtering
 * - Create/Edit forms with validation
 * - Detail/View mode for read-only display
 * - Bulk actions (delete, export)
 * - Permission-based access control
 * - Error handling and user feedback
 * - Keyboard shortcuts support
 * 
 * @example
 * <DynamicCRUDView
 *   module="accounting"
 *   entity="accounts"
 *   :mode="viewMode"
 *   :id="recordId"
 * />
 */

import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useMetadataStore } from '@/stores/metadata';
import { useUiStore } from '@/stores/ui';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts';
import DynamicTable from '@/components/tables/DynamicTable.vue';
import DynamicForm from '@/components/forms/DynamicForm.vue';
import type {
  FormFieldMetadata
} from '@/types/metadata';
import type { ApiError } from '@/types/api';
import { getErrorMessage, getValidationErrors } from '@/types/api';
import api from '@/api/client';

/**
 * Entity metadata structure
 * Defines the configuration for an entity including API endpoints and permissions
 */
interface EntityMetadata {
  id: string;
  label: string;
  singular?: string;
  description?: string;
  apiEndpoint: string;
  permissions: {
    view: string;
    create: string;
    update: string;
    delete: string;
  };
}

/**
 * Component props
 */
const props = defineProps<{
  /** Module identifier (e.g., 'accounting', 'sales') */
  moduleId: string;
  /** Entity identifier (e.g., 'accounts', 'customers') */
  entityId: string;
  /** 
   * Record ID for edit/view modes
   * Note: Route params are strings, but backend IDs may be numbers.
   * The API client handles type conversion as needed.
   */
  recordId?: string | number;
  /** View mode override (auto-detected from route if not provided) */
  mode?: 'list' | 'create' | 'edit' | 'view';
}>();

// ============================================================================
// State Management
// ============================================================================

// Router & Store
const route = useRoute();
const router = useRouter();
const metadataStore = useMetadataStore();
const uiStore = useUiStore();
const { confirm } = useConfirmDialog();

// Component State
/** Loading state for async operations */
const loading = ref(false);
/** Submitting state for form operations */
const submitting = ref(false);
/** Table data for list view */
const tableData = ref<any[]>([]);
/** Form data for create/edit/view modes */
const formData = ref<any>({});
/** Pagination information */
const pagination = ref({
  currentPage: 1,
  totalPages: 1,
  perPage: 20,
  totalRecords: 0,
  hasNextPage: false,
  hasPreviousPage: false
});
/** Active filters - sent to backend as filter[field]=value */
const filters = ref<Record<string, any>>({});
/** Current sort column */
const sortColumn = ref<string>('');
/** Current sort direction */
const sortDirection = ref<'asc' | 'desc'>('asc');

// ============================================================================
// Computed Properties
// ============================================================================

/**
 * Determines current view mode based on props or route
 * Modes: list (default), create, edit, view
 */
const mode = computed(() => {
  if (props.mode) return props.mode;
  if (props.recordId && route.name?.toString().includes('edit')) return 'edit';
  if (props.recordId) return 'view';
  if (route.name?.toString().includes('create')) return 'create';
  return 'list';
});

/**
 * Entity metadata loaded from backend
 * Falls back to generated metadata if not available
 */
const entityMetadata = computed<EntityMetadata>(() => {
  const module = metadataStore.moduleMetadata[props.moduleId];
  const entities = (module as any)?.config?.entities || {};
  const entity = entities[props.entityId];
  
  return entity || {
    id: props.entityId,
    label: props.entityId,
    singular: props.entityId,
    apiEndpoint: `/api/${props.moduleId}/${props.entityId}`,
    permissions: {
      view: `${props.moduleId}.${props.entityId}.view`,
      create: `${props.moduleId}.${props.entityId}.create`,
      update: `${props.moduleId}.${props.entityId}.update`,
      delete: `${props.moduleId}.${props.entityId}.delete`
    }
  };
});

/** Permission strings for CRUD operations */
const createPermission = computed(() => entityMetadata.value.permissions.create);
const updatePermission = computed(() => entityMetadata.value.permissions.update);
const deletePermission = computed(() => entityMetadata.value.permissions.delete);
const viewPermission = computed(() => entityMetadata.value.permissions.view);

/** Table configuration metadata */
const tableMetadata = ref<any>(null);
/** Form configuration metadata */
const formMetadata = ref<any>(null);

/**
 * Generates a title for detail view from record data
 * Uses name, title, or code field if available
 */
const recordTitle = computed(() => {
  if (!formData.value) return '';
  return formData.value.name || formData.value.title || formData.value.code || '';
});

/**
 * Fields to display in detail view
 * Filters out hidden and password fields
 */
const displayFields = computed(() => {
  if (!formMetadata.value) return [];
  return formMetadata.value.sections
    .flatMap((section: any) => section.fields)
    .filter((field: any) => field.visible !== false && field.type !== 'password');
});

// ============================================================================
// Methods
// ============================================================================

/**
 * Checks if user has a specific permission
 * @param permission - Permission string to check
 * @returns True if user has permission
 */
const hasPermission = (permission: string) => {
  return metadataStore.hasPermission(permission);
};

/**
 * Loads table metadata configuration from backend
 * Falls back to basic configuration if metadata not available
 */
const loadTableMetadata = async () => {
  try {
    const tableId = `${props.moduleId}.${props.entityId}.list`;
    tableMetadata.value = await metadataStore.loadTableMetadata(tableId);
  } catch (error: unknown) {
    console.error('Failed to load table metadata:', error);
    uiStore.showError('Error', 'Failed to load table configuration. Using default settings.');
    // Fallback to basic configuration
    tableMetadata.value = {
      id: `${props.moduleId}.${props.entityId}.list`,
      title: entityMetadata.value.label,
      apiEndpoint: entityMetadata.value.apiEndpoint,
      columns: [],
      searchable: true,
      sortable: true,
      pagination: {
        enabled: true,
        pageSize: 20,
        pageSizeOptions: [10, 20, 50, 100]
      },
      exportable: true
    };
  }
};

const loadFormMetadata = async () => {
  try {
    const formId = `${props.moduleId}.${props.entityId}.${mode.value === 'create' ? 'create' : 'edit'}`;
    formMetadata.value = await metadataStore.loadFormMetadata(formId);
  } catch (error: unknown) {
    console.error('Failed to load form metadata:', error);
    uiStore.showError('Error', 'Failed to load form configuration. Please try again later.');
  }
};

const loadTableData = async () => {
  if (!hasPermission(viewPermission.value)) {
    uiStore.showWarning('Access Denied', 'You do not have permission to view this data.');
    return;
  }

  loading.value = true;
  try {
    const params: Record<string, any> = {
      page: pagination.value.currentPage,
      per_page: pagination.value.perPage
    };
    
    // Add sort parameters if set
    if (sortColumn.value) {
      params.sort_by = sortColumn.value;
      params.sort_direction = sortDirection.value;
    }
    
    // Add filter parameters
    Object.keys(filters.value).forEach(key => {
      if (filters.value[key] !== null && filters.value[key] !== undefined && filters.value[key] !== '') {
        params[`filter[${key}]`] = filters.value[key];
      }
    });
    
    const response = await api.get(entityMetadata.value.apiEndpoint, { params });
    
    if (response.data.success) {
      tableData.value = response.data.data;
      if (response.data.pagination) {
        pagination.value = response.data.pagination;
      }
    }
  } catch (error: unknown) {
    console.error('Failed to load table data:', error);
    uiStore.showError('Error', getErrorMessage(error));
  } finally {
    loading.value = false;
  }
};

const loadRecordData = async () => {
  if (!props.recordId) return;

  loading.value = true;
  try {
    const response = await api.get(`${entityMetadata.value.apiEndpoint}/${props.recordId}`);
    
    if (response.data.success) {
      formData.value = response.data.data;
    }
  } catch (error: unknown) {
    console.error('Failed to load record data:', error);
    uiStore.showError('Error', getErrorMessage(error));
  } finally {
    loading.value = false;
  }
};

const handleFormSubmit = async (data: any) => {
  if (!hasPermission(mode.value === 'create' ? createPermission.value : updatePermission.value)) {
    uiStore.showWarning('Access Denied', 'You do not have permission to perform this action.');
    return;
  }

  submitting.value = true;
  try {
    let response;
    
    if (mode.value === 'create') {
      response = await api.post(entityMetadata.value.apiEndpoint, data);
    } else {
      response = await api.put(`${entityMetadata.value.apiEndpoint}/${props.recordId}`, data);
    }

    if (response.data.success) {
      const actionLabel = mode.value === 'create' ? 'created' : 'updated';
      uiStore.showSuccess('Success', `${entityMetadata.value.singular || 'Record'} ${actionLabel} successfully.`);
      
      if (mode.value === 'create' && response.data.data?.id) {
        router.push({
          name: `${props.moduleId}-${props.entityId}-detail`,
          params: { id: response.data.data.id }
        });
      } else {
        navigateToList();
      }
    }
  } catch (error: unknown) {
    console.error('Failed to save record:', error);
    
    // Show validation errors if available
    const validationErrors = getValidationErrors(error);
    if (validationErrors) {
      const errorList = Object.entries(validationErrors)
        .flatMap(([field, errors]) => errors.map(err => `• ${field}: ${err}`))
        .join('\n');
      uiStore.showError('Validation Error', errorList);
    } else {
      uiStore.showError('Error', getErrorMessage(error));
    }
  } finally {
    submitting.value = false;
  }
};

const handleDelete = async () => {
  if (!hasPermission(deletePermission.value) || !props.recordId) {
    uiStore.showWarning('Access Denied', 'You do not have permission to delete this record.');
    return;
  }

  const confirmed = await confirm({
    title: 'Confirm Delete',
    message: `Are you sure you want to delete this ${entityMetadata.value.singular || 'record'}? This action cannot be undone.`,
    type: 'danger',
    confirmText: 'Delete',
    cancelText: 'Cancel',
  });

  if (!confirmed) {
    return;
  }

  loading.value = true;
  try {
    const response = await api.delete(`${entityMetadata.value.apiEndpoint}/${props.recordId}`);
    
    if (response.data.success) {
      uiStore.showSuccess('Success', `${entityMetadata.value.singular || 'Record'} deleted successfully.`);
      navigateToList();
    }
  } catch (error: unknown) {
    console.error('Failed to delete record:', error);
    uiStore.showError('Error', getErrorMessage(error));
  } finally {
    loading.value = false;
  }
};

const handleRowAction = async (action: string, row: any) => {
  switch (action) {
    case 'view':
      router.push({
        name: `${props.moduleId}-${props.entityId}-detail`,
        params: { id: row.id }
      });
      break;
    case 'edit':
      router.push({
        name: `${props.moduleId}-${props.entityId}-edit`,
        params: { id: row.id }
      });
      break;
    case 'delete':
      {
        const confirmed = await confirm({
          title: 'Confirm Delete',
          message: `Are you sure you want to delete this ${entityMetadata.value.singular || 'record'}? This action cannot be undone.`,
          type: 'danger',
          confirmText: 'Delete',
          cancelText: 'Cancel',
        });

        if (confirmed) {
          try {
            const response = await api.delete(`${entityMetadata.value.apiEndpoint}/${row.id}`);
            if (response.data.success) {
              uiStore.showSuccess('Success', `${entityMetadata.value.singular || 'Record'} deleted successfully.`);
              await loadTableData();
            }
          } catch (error: unknown) {
            console.error('Failed to delete record:', error);
            uiStore.showError('Error', getErrorMessage(error));
          }
        }
      }
      break;
  }
};

const handleBulkAction = async (action: string, rows: any[]) => {
  if (!rows || rows.length === 0) {
    uiStore.showWarning('No Selection', 'Please select at least one record.');
    return;
  }

  switch (action) {
    case 'delete':
      if (!hasPermission(deletePermission.value)) {
        uiStore.showWarning('Access Denied', 'You do not have permission to delete records.');
        return;
      }

      const confirmed = await confirm({
        title: 'Confirm Bulk Delete',
        message: `Are you sure you want to delete ${rows.length} ${rows.length === 1 ? 'record' : 'records'}? This action cannot be undone.`,
        type: 'danger',
        confirmText: 'Delete',
        cancelText: 'Cancel',
      });

      if (confirmed) {
        loading.value = true;
        try {
          const ids = rows.map(row => row.id);
          const response = await api.post(`${entityMetadata.value.apiEndpoint}/bulk-delete`, { ids });
          
          if (response.data.success) {
            uiStore.showSuccess('Success', `${rows.length} ${rows.length === 1 ? 'record' : 'records'} deleted successfully.`);
            await loadTableData();
          }
        } catch (error: unknown) {
          console.error('Failed to delete records:', error);
          uiStore.showError('Error', getErrorMessage(error));
        } finally {
          loading.value = false;
        }
      }
      break;
    
    case 'export':
      try {
        const ids = rows.map(row => row.id);
        const response = await api.post(`${entityMetadata.value.apiEndpoint}/export`, 
          { ids },
          { responseType: 'blob' }
        );
        
        // Create download link with readable filename
        const now = new Date();
        const dateStr = now.toISOString().slice(0, 19).replace('T', '_').replace(/:/g, '-');
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `${props.entityId}_export_${dateStr}.csv`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url); // Clean up memory
        
        uiStore.showSuccess('Success', 'Data exported successfully.');
      } catch (error: unknown) {
        console.error('Failed to export data:', error);
        uiStore.showError('Error', getErrorMessage(error));
      }
      break;
    
    default:
      console.log('Bulk action:', action, rows);
      uiStore.showInfo('Info', `Bulk action "${action}" is not yet implemented.`);
  }
};

const handlePageChange = (page: number) => {
  pagination.value.currentPage = page;
  loadTableData();
};

const handleSortChange = (sort: any) => {
  if (sort.column) {
    sortColumn.value = sort.column;
    sortDirection.value = sort.direction || 'asc';
  }
  loadTableData();
};

const handleFilterChange = (newFilters: any) => {
  filters.value = { ...newFilters };
  // Reset to first page when filters change
  pagination.value.currentPage = 1;
  loadTableData();
};

const navigateToList = () => {
  router.push({
    name: `${props.moduleId}-${props.entityId}-list`
  });
};

const navigateToCreate = () => {
  router.push({
    name: `${props.moduleId}-${props.entityId}-create`
  });
};

const navigateToEdit = () => {
  router.push({
    name: `${props.moduleId}-${props.entityId}-edit`,
    params: { id: props.recordId }
  });
};

const formatFieldValue = (field: FormFieldMetadata, value: any) => {
  if (value === null || value === undefined || value === '') {
    return '-';
  }

  switch (field.type) {
    case 'date':
      return new Date(value).toLocaleDateString();
    case 'datetime':
      return new Date(value).toLocaleString();
    case 'checkbox':
      return value ? 'Yes' : 'No';
    case 'select':
    case 'radio':
      const option = field.options?.find(opt => opt.value === value);
      return option?.label || value;
    default:
      return value;
  }
};

// Lifecycle
onMounted(async () => {
  if (mode.value === 'list') {
    await loadTableMetadata();
    await loadTableData();
  } else {
    await loadFormMetadata();
    if (props.recordId && (mode.value === 'edit' || mode.value === 'view')) {
      await loadRecordData();
    }
  }
});

// Watch for route changes
watch(() => [props.recordId, props.mode], async () => {
  if (mode.value === 'list') {
    await loadTableMetadata();
    await loadTableData();
  } else {
    await loadFormMetadata();
    if (props.recordId && (mode.value === 'edit' || mode.value === 'view')) {
      await loadRecordData();
    }
  }
}, { immediate: false });

// Keyboard shortcuts
useKeyboardShortcuts([
  {
    key: 'n',
    ctrl: true,
    description: 'Create new record',
    callback: () => {
      if (mode.value === 'list' && hasPermission(createPermission.value)) {
        navigateToCreate();
      }
    },
  },
  {
    key: 'e',
    ctrl: true,
    description: 'Edit current record',
    callback: () => {
      if (mode.value === 'view' && hasPermission(updatePermission.value)) {
        navigateToEdit();
      }
    },
  },
  {
    key: 'Escape',
    description: 'Go back to list',
    callback: () => {
      if (mode.value !== 'list') {
        navigateToList();
      }
    },
  },
]);
</script>

<style scoped>
.dynamic-crud-view {
  @apply p-6;
}
</style>
