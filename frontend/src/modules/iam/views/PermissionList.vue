<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Permissions</h1>
      <button @click="openCreateModal" class="btn btn-primary">
        <PlusIcon class="icon" />
        Add Permission
      </button>
    </div>
    
    <div class="filters-section">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by name or slug..."
        class="search-input"
        @input="fetchPermissions"
      />
      <select v-model="filters.module" @change="fetchPermissions" class="filter-select">
        <option value="">All Modules</option>
        <option v-for="module in modules" :key="module" :value="module">
          {{ module }}
        </option>
      </select>
    </div>
    
    <div class="permissions-by-module">
      <div v-for="module in displayedModules" :key="module" class="module-group">
        <div class="module-header">
          <h3 class="module-title">{{ module }}</h3>
          <span class="module-count">{{ getModulePermissions(module).length }} permissions</span>
        </div>
        
        <DataTable
          :columns="columns"
          :data="getModulePermissions(module)"
          :loading="loading"
          :show-pagination="false"
        >
          <template #actions="{ row }">
            <div class="action-buttons">
              <button @click="editPermission(row)" class="btn-icon" title="Edit">
                <PencilIcon class="icon" />
              </button>
              <button @click="deletePermission(row)" class="btn-icon btn-danger" title="Delete">
                <TrashIcon class="icon" />
              </button>
            </div>
          </template>
        </DataTable>
      </div>
    </div>
    
    <Modal
      :show="showModal"
      :title="isEditMode ? 'Edit Permission' : 'Create Permission'"
      @close="closeModal"
      @confirm="handleSavePermission"
    >
      <div class="form-section">
        <FormInput
          v-model="form.name"
          label="Permission Name"
          placeholder="e.g., View Products"
          required
        />
        
        <FormInput
          v-model="form.slug"
          label="Slug"
          placeholder="e.g., products.view"
          required
        />
        
        <FormInput
          v-model="form.module"
          label="Module"
          placeholder="e.g., inventory"
          required
        />
        
        <FormTextarea
          v-model="form.description"
          label="Description"
          rows="3"
          placeholder="Describe what this permission allows"
        />
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'
import iamService from '../../../services/iamService'

const loading = ref(false)
const showModal = ref(false)
const isEditMode = ref(false)
const permissions = ref([])
const currentPage = ref(1)
const totalPages = ref(1)

const filters = ref({
  search: '',
  module: ''
})

const form = ref({
  name: '',
  slug: '',
  module: '',
  description: ''
})

const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'slug', label: 'Slug', sortable: true },
  { key: 'description', label: 'Description' }
]

const modules = computed(() => {
  const moduleSet = new Set()
  permissions.value.forEach(p => {
    if (p.module) moduleSet.add(p.module)
  })
  return Array.from(moduleSet).sort()
})

const displayedModules = computed(() => {
  if (filters.value.module) {
    return [filters.value.module]
  }
  return modules.value
})

const getModulePermissions = (module) => {
  let perms = permissions.value.filter(p => p.module === module)
  
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    perms = perms.filter(p => 
      p.name.toLowerCase().includes(search) || 
      p.slug.toLowerCase().includes(search)
    )
  }
  
  return perms
}

onMounted(() => {
  fetchPermissions()
})

const fetchPermissions = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value
    }
    const response = await iamService.getPermissions(params)
    permissions.value = response.data.data || []
    totalPages.value = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch permissions:', error)
    permissions.value = []
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  isEditMode.value = false
  resetForm()
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const resetForm = () => {
  form.value = {
    name: '',
    slug: '',
    module: '',
    description: ''
  }
}

const handleSavePermission = async () => {
  try {
    if (isEditMode.value) {
      await iamService.updatePermission(form.value.id, form.value)
    } else {
      await iamService.createPermission(form.value)
    }
    closeModal()
    await fetchPermissions()
  } catch (error) {
    console.error('Failed to save permission:', error)
    alert('Failed to save permission. Please try again.')
  }
}

const editPermission = async (permission) => {
  form.value = { ...permission }
  isEditMode.value = true
  showModal.value = true
}

const deletePermission = async (permission) => {
  if (confirm(`Are you sure you want to delete permission "${permission.name}"?`)) {
    try {
      await iamService.deletePermission(permission.id)
      await fetchPermissions()
    } catch (error) {
      console.error('Failed to delete permission:', error)
      alert('Failed to delete permission. Please try again.')
    }
  }
}
</script>

<style scoped>
.page-container {
  max-width: 1400px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.icon {
  width: 20px;
  height: 20px;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
  color: #6b7280;
  transition: color 0.2s;
  border-radius: 4px;
}

.btn-icon:hover {
  color: #3b82f6;
  background: #eff6ff;
}

.text-muted {
  color: #9ca3af;
  font-size: 14px;
}
</style>

<style scoped>
.page-container {
  max-width: 1400px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
}

.filters-section {
  margin-bottom: 20px;
  display: flex;
  gap: 12px;
}

.search-input {
  flex: 1;
  max-width: 500px;
  padding: 10px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-select {
  padding: 10px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  background: white;
  min-width: 200px;
}

.filter-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.icon {
  width: 20px;
  height: 20px;
}

.permissions-by-module {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.module-group {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.module-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.module-title {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
  text-transform: capitalize;
  margin: 0;
}

.module-count {
  font-size: 14px;
  color: #6b7280;
  font-weight: 500;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
  color: #6b7280;
  transition: color 0.2s;
  border-radius: 4px;
}

.btn-icon:hover {
  color: #3b82f6;
  background: #eff6ff;
}

.btn-icon.btn-danger:hover {
  color: #ef4444;
  background: #fef2f2;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
</style>
