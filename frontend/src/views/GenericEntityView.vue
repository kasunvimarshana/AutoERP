<template>
  <div class="generic-entity-view">
    <!-- Breadcrumbs -->
    <nav v-if="breadcrumbs.length > 0" class="breadcrumb-nav" aria-label="Breadcrumb">
      <ol class="breadcrumb">
        <li v-for="(crumb, index) in breadcrumbs" :key="index" class="breadcrumb-item">
          <router-link v-if="index < breadcrumbs.length - 1" :to="crumb.path">
            {{ crumb.label }}
          </router-link>
          <span v-else aria-current="page">{{ crumb.label }}</span>
        </li>
      </ol>
    </nav>

    <!-- Page Header -->
    <header class="page-header">
      <h1 class="page-title">{{ pageTitle }}</h1>
      <div v-if="viewType === 'list'" class="page-actions">
        <button
          v-if="canCreate"
          @click="handleCreate"
          class="btn btn-primary"
          v-permission="entityMetadata?.permissions?.create"
        >
          <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Create {{ entityMetadata?.label }}
        </button>
      </div>
    </header>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="spinner" role="status" aria-live="polite">
        <span class="sr-only">Loading...</span>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container" role="alert">
      <p class="error-message">{{ error }}</p>
      <button @click="loadData" class="btn btn-secondary">
        Retry
      </button>
    </div>

    <!-- List View -->
    <DynamicTable
      v-else-if="viewType === 'list'"
      :fields="listFields"
      :data="items"
      :loading="loading"
      :pagination="pagination"
      @create="handleCreate"
      @edit="handleEdit"
      @delete="handleDelete"
      @page-change="handlePageChange"
      @sort="handleSort"
      @search="handleSearch"
    />

    <!-- Form View (Create/Edit) -->
    <DynamicForm
      v-else-if="viewType === 'create' || viewType === 'edit'"
      :fields="formFields"
      v-model="formData"
      :loading="saving"
      :submit-label="viewType === 'create' ? 'Create' : 'Update'"
      @submit="handleSubmit"
      @cancel="handleCancel"
    />

    <!-- Detail View -->
    <div v-else-if="viewType === 'detail'" class="detail-view">
      <div class="detail-actions">
        <button
          v-if="canEdit"
          @click="handleEditCurrent"
          class="btn btn-primary"
          v-permission="entityMetadata?.permissions?.edit"
        >
          Edit
        </button>
        <button
          v-if="canDelete"
          @click="handleDeleteCurrent"
          class="btn btn-danger"
          v-permission="entityMetadata?.permissions?.delete"
        >
          Delete
        </button>
        <button @click="handleBack" class="btn btn-secondary">
          Back to List
        </button>
      </div>

      <div class="detail-content">
        <div v-for="field in detailFields" :key="field.name" class="detail-field">
          <label class="detail-label">{{ field.label }}</label>
          <div class="detail-value">
            <component
              :is="getFieldComponent(field)"
              :value="currentItem?.[field.name]"
              :field="field"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useMetadataStore } from '../stores/metadataStore';
import { generateBreadcrumbs } from '../services/dynamicRoutesService';
import DynamicTable from '../components/common/DynamicTable.vue';
import DynamicForm from '../components/common/DynamicForm.vue';
import { usePermissions } from '../composables/usePermissions';
import apiClient from '../services/api';

const route = useRoute();
const router = useRouter();
const metadataStore = useMetadataStore();
const { hasPermission } = usePermissions();

// View State
const entityName = computed(() => route.meta.entity);
const viewType = computed(() => route.meta.viewType);
const pageTitle = computed(() => route.meta.title);

// Data State
const loading = ref(false);
const saving = ref(false);
const error = ref(null);
const entityMetadata = ref(null);
const listFields = ref([]);
const formFields = ref([]);
const detailFields = ref([]);
const items = ref([]);
const currentItem = ref(null);
const formData = ref({});
const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1,
});

// Breadcrumbs
const breadcrumbs = computed(() => {
  return generateBreadcrumbs(route, route.params);
});

// Permissions
const canCreate = computed(() => {
  return hasPermission.value(entityMetadata.value?.permissions?.create);
});

const canEdit = computed(() => {
  return hasPermission.value(entityMetadata.value?.permissions?.edit);
});

const canDelete = computed(() => {
  return hasPermission.value(entityMetadata.value?.permissions?.delete);
});

// Initialize
onMounted(async () => {
  await loadMetadata();
  await loadData();
});

// Watch for route changes
watch(() => route.params.id, async (newId) => {
  if (newId && (viewType.value === 'edit' || viewType.value === 'detail')) {
    await loadData();
  }
});

// Load metadata for entity
async function loadMetadata() {
  try {
    loading.value = true;
    error.value = null;

    entityMetadata.value = await metadataStore.fetchEntity(entityName.value);

    if (viewType.value === 'list') {
      listFields.value = await metadataStore.fetchFieldConfig(entityName.value, 'list');
    } else if (viewType.value === 'create' || viewType.value === 'edit') {
      formFields.value = await metadataStore.fetchFieldConfig(entityName.value, 'form');
    } else if (viewType.value === 'detail') {
      detailFields.value = await metadataStore.fetchFieldConfig(entityName.value, 'detail');
    }
  } catch (err) {
    console.error('Failed to load metadata:', err);
    error.value = 'Failed to load metadata configuration';
  } finally {
    loading.value = false;
  }
}

// Load data
async function loadData() {
  try {
    loading.value = true;
    error.value = null;

    if (viewType.value === 'list') {
      // Load list data
      await loadListData();
    } else if (viewType.value === 'edit' || viewType.value === 'detail') {
      // Load single item
      await loadSingleItem(route.params.id);
    }
  } catch (err) {
    console.error('Failed to load data:', err);
    error.value = 'Failed to load data';
  } finally {
    loading.value = false;
  }
}

// Load list data
async function loadListData(page = 1, search = '', sort = {}) {
  // This would call the appropriate API service based on entity metadata
  // For now, this is a placeholder
  const apiPath = entityMetadata.value?.api_config?.base_path;
  if (!apiPath) {
    throw new Error('API path not configured');
  }

  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value
    };
    
    if (searchTerm.value) {
      params.search = searchTerm.value;
    }
    
    const response = await apiClient.get(apiPath, { params });
    items.value = response.data || [];
    
    // Handle pagination metadata
    if (response.meta) {
      pagination.value = response.meta;
      totalPages.value = response.meta.last_page || 1;
    } else if (response.pagination) {
      pagination.value = response.pagination;
      totalPages.value = response.pagination.total_pages || 1;
    }
  } catch (err) {
    console.error('Failed to load data:', err);
    items.value = [];
    throw err;
  }
}

// Load single item
async function loadSingleItem(id) {
  const apiPath = entityMetadata.value?.api_config?.base_path;
  if (!apiPath) {
    throw new Error('API path not configured');
  }

  try {
    const response = await apiClient.get(`${apiPath}/${id}`);
    currentItem.value = response.data || response;
    
    if (viewType.value === 'edit') {
      formData.value = { ...currentItem.value };
    }
  } catch (err) {
    console.error('Failed to load item:', err);
    currentItem.value = {};
    if (viewType.value === 'edit') {
      formData.value = {};
    }
    throw err;
  }
}

// Handlers
function handleCreate() {
  router.push({ name: `${entityName.value}_create` });
}

function handleEdit(item) {
  router.push({ name: `${entityName.value}_edit`, params: { id: item.id } });
}

function handleEditCurrent() {
  router.push({ name: `${entityName.value}_edit`, params: { id: route.params.id } });
}

async function handleDelete(item) {
  if (!confirm(`Are you sure you want to delete this ${entityMetadata.value?.label}?`)) {
    return;
  }

  try {
    const apiPath = entityMetadata.value?.api_config?.base_path;
    if (!apiPath) {
      throw new Error('API path not configured');
    }
    
    await apiClient.delete(`${apiPath}/${item.id}`);
    await loadData();
  } catch (err) {
    console.error('Failed to delete:', err);
    alert('Failed to delete item');
  }
}

async function handleDeleteCurrent() {
  if (!confirm(`Are you sure you want to delete this ${entityMetadata.value?.label}?`)) {
    return;
  }

  try {
    const apiPath = entityMetadata.value?.api_config?.base_path;
    if (!apiPath) {
      throw new Error('API path not configured');
    }
    
    await apiClient.delete(`${apiPath}/${route.params.id}`);
    router.push({ name: `${entityName.value}_list` });
  } catch (err) {
    console.error('Failed to delete:', err);
    alert('Failed to delete item');
  }
}

async function handleSubmit(data) {
  try {
    saving.value = true;

    const apiPath = entityMetadata.value?.api_config?.base_path;
    if (!apiPath) {
      throw new Error('API path not configured');
    }

    if (viewType.value === 'create') {
      await apiClient.post(apiPath, data);
    } else if (viewType.value === 'edit') {
      await apiClient.put(`${apiPath}/${route.params.id}`, data);
    }

    router.push({ name: `${entityName.value}_list` });
  } catch (err) {
    console.error('Failed to save:', err);
    alert('Failed to save data');
  } finally {
    saving.value = false;
  }
}

function handleCancel() {
  router.back();
}

function handleBack() {
  router.push({ name: `${entityName.value}_list` });
}

function handlePageChange(page) {
  loadListData(page);
}

function handleSort(sort) {
  loadListData(pagination.value.current_page, '', sort);
}

function handleSearch(search) {
  loadListData(1, search);
}

function getFieldComponent(field) {
  // Return appropriate component based on field type
  return 'div';
}
</script>

<style scoped>
.generic-entity-view {
  padding: 1.5rem;
}

/* Breadcrumbs */
.breadcrumb-nav {
  margin-bottom: 1.5rem;
}

.breadcrumb {
  display: flex;
  flex-wrap: wrap;
  padding: 0;
  margin: 0;
  list-style: none;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
}

.breadcrumb-item + .breadcrumb-item::before {
  content: '/';
  padding: 0 0.5rem;
  color: #6b7280;
}

.breadcrumb-item a {
  color: #3b82f6;
  text-decoration: none;
}

.breadcrumb-item a:hover {
  text-decoration: underline;
}

.breadcrumb-item span {
  color: #6b7280;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.page-title {
  font-size: 1.875rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.page-actions {
  display: flex;
  gap: 0.75rem;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: 1px solid transparent;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease-in-out;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background-color: #2563eb;
}

.btn-secondary {
  background-color: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background-color: #4b5563;
}

.btn-danger {
  background-color: #ef4444;
  color: white;
}

.btn-danger:hover {
  background-color: #dc2626;
}

.icon {
  width: 1.25rem;
  height: 1.25rem;
}

/* Loading */
.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 3rem;
}

.spinner {
  width: 3rem;
  height: 3rem;
  border: 3px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

/* Error */
.error-container {
  padding: 2rem;
  text-align: center;
}

.error-message {
  color: #ef4444;
  font-size: 1rem;
  margin-bottom: 1rem;
}

/* Detail View */
.detail-view {
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
}

.detail-actions {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.detail-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.detail-field {
  display: flex;
  flex-direction: column;
}

.detail-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.detail-value {
  font-size: 1rem;
  color: #111827;
}
</style>
