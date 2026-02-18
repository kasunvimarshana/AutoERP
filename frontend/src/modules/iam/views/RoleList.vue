<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Roles</h1>
      <button @click="openCreateModal" class="btn btn-primary">
        <PlusIcon class="icon" />
        Add Role
      </button>
    </div>
    
    <div class="filters-section">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by name or description..."
        class="search-input"
        @input="fetchRoles"
      />
    </div>
    
    <DataTable
      :columns="columns"
      :data="roles"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-permissions_count="{ row }">
        <span class="badge badge-blue">{{ row.permissions_count || 0 }} permissions</span>
      </template>
      
      <template #cell-users_count="{ row }">
        <span class="badge badge-green">{{ row.users_count || 0 }} users</span>
      </template>
      
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewRole(row)" class="btn-icon" title="View">
            <EyeIcon class="icon" />
          </button>
          <button @click="editRole(row)" class="btn-icon" title="Edit">
            <PencilIcon class="icon" />
          </button>
          <button @click="managePermissions(row)" class="btn-icon" title="Assign Permissions">
            <ShieldCheckIcon class="icon" />
          </button>
          <button @click="deleteRole(row)" class="btn-icon btn-danger" title="Delete">
            <TrashIcon class="icon" />
          </button>
        </div>
      </template>
    </DataTable>
    
    <Modal
      :show="showModal"
      :title="isEditMode ? 'Edit Role' : 'Create Role'"
      size="large"
      @close="closeModal"
      @confirm="handleSaveRole"
    >
      <div class="form-section">
        <div class="form-grid">
          <FormInput
            v-model="form.name"
            label="Role Name"
            placeholder="Enter role name"
            required
          />
          <FormInput
            v-model="form.slug"
            label="Slug"
            placeholder="role-slug"
            required
          />
        </div>
        
        <FormTextarea
          v-model="form.description"
          label="Description"
          rows="3"
          placeholder="Describe the role and its responsibilities"
        />
        
        <div class="permissions-section">
          <h4>Permissions</h4>
          <div class="permissions-grid">
            <div v-for="module in permissionModules" :key="module" class="permission-module">
              <div class="module-header">
                <input
                  type="checkbox"
                  :id="`module-${module}`"
                  :checked="isModuleSelected(module)"
                  @change="toggleModule(module)"
                  class="module-checkbox"
                />
                <label :for="`module-${module}`" class="module-label">{{ module }}</label>
              </div>
              <div class="permission-list">
                <div
                  v-for="permission in getModulePermissions(module)"
                  :key="permission.id"
                  class="permission-item"
                >
                  <input
                    type="checkbox"
                    :id="`perm-${permission.id}`"
                    :value="permission.id"
                    v-model="form.permission_ids"
                    class="permission-checkbox"
                  />
                  <label :for="`perm-${permission.id}`" class="permission-label">
                    {{ permission.name }}
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Modal>
    
    <Modal
      :show="showViewModal"
      title="Role Details"
      size="large"
      hide-footer
      @close="showViewModal = false"
    >
      <div v-if="selectedRole" class="view-details">
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Name:</span>
            <span class="detail-value">{{ selectedRole.name }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Slug:</span>
            <span class="detail-value">{{ selectedRole.slug }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Permissions:</span>
            <span class="badge badge-blue">{{ selectedRole.permissions_count || 0 }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Users:</span>
            <span class="badge badge-green">{{ selectedRole.users_count || 0 }}</span>
          </div>
        </div>
        
        <div v-if="selectedRole.description" class="detail-section">
          <h4>Description</h4>
          <p>{{ selectedRole.description }}</p>
        </div>
        
        <div v-if="selectedRole.permissions && selectedRole.permissions.length" class="detail-section">
          <h4>Assigned Permissions</h4>
          <div class="permission-chips">
            <span
              v-for="permission in selectedRole.permissions"
              :key="permission.id"
              class="permission-chip"
            >
              {{ permission.name }}
            </span>
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'
import iamService from '../../../services/iamService'

const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const roles = ref([])
const permissions = ref([])
const selectedRole = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)

const filters = ref({
  search: ''
})

const form = ref({
  name: '',
  slug: '',
  description: '',
  permission_ids: []
})

const columns = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'slug', label: 'Slug', sortable: true },
  { key: 'description', label: 'Description' },
  { key: 'permissions_count', label: 'Permissions' },
  { key: 'users_count', label: 'Users' }
]

const permissionModules = computed(() => {
  const modules = new Set()
  permissions.value.forEach(p => {
    if (p.module) modules.add(p.module)
  })
  return Array.from(modules).sort()
})

onMounted(async () => {
  await Promise.all([
    fetchRoles(),
    fetchPermissions()
  ])
})

const fetchRoles = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      search: filters.value.search
    }
    const response = await iamService.getRoles(params)
    roles.value = response.data.data || []
    totalPages.value = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch roles:', error)
    roles.value = []
  } finally {
    loading.value = false
  }
}

const fetchPermissions = async () => {
  try {
    const response = await iamService.getPermissions()
    permissions.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch permissions:', error)
  }
}

const getModulePermissions = (module) => {
  return permissions.value.filter(p => p.module === module)
}

const isModuleSelected = (module) => {
  const modulePerms = getModulePermissions(module)
  return modulePerms.every(p => form.value.permission_ids.includes(p.id))
}

const toggleModule = (module) => {
  const modulePerms = getModulePermissions(module)
  const modulePermIds = modulePerms.map(p => p.id)
  
  if (isModuleSelected(module)) {
    form.value.permission_ids = form.value.permission_ids.filter(
      id => !modulePermIds.includes(id)
    )
  } else {
    const newIds = modulePermIds.filter(id => !form.value.permission_ids.includes(id))
    form.value.permission_ids.push(...newIds)
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchRoles()
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
    description: '',
    permission_ids: []
  }
}

const handleSaveRole = async () => {
  try {
    if (isEditMode.value) {
      await iamService.updateRole(form.value.id, form.value)
    } else {
      await iamService.createRole(form.value)
    }
    closeModal()
    await fetchRoles()
  } catch (error) {
    console.error('Failed to save role:', error)
    alert('Failed to save role. Please try again.')
  }
}

const viewRole = async (role) => {
  try {
    const response = await iamService.getRole(role.id)
    selectedRole.value = response.data.data
    showViewModal.value = true
  } catch (error) {
    console.error('Failed to fetch role details:', error)
  }
}

const editRole = async (role) => {
  try {
    const response = await iamService.getRole(role.id)
    const data = response.data.data
    form.value = {
      id: data.id,
      name: data.name,
      slug: data.slug,
      description: data.description || '',
      permission_ids: data.permissions ? data.permissions.map(p => p.id) : []
    }
    isEditMode.value = true
    showModal.value = true
  } catch (error) {
    console.error('Failed to fetch role:', error)
  }
}

const managePermissions = (role) => {
  editRole(role)
}

const deleteRole = async (role) => {
  if (confirm(`Are you sure you want to delete role "${role.name}"?`)) {
    try {
      await iamService.deleteRole(role.id)
      await fetchRoles()
    } catch (error) {
      console.error('Failed to delete role:', error)
      alert('Failed to delete role. Please try again.')
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

.filters-section {
  margin-bottom: 20px;
}

.search-input {
  width: 100%;
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

.btn-icon.btn-danger:hover {
  color: #ef4444;
  background: #fef2f2;
}

.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.badge-blue {
  background: #dbeafe;
  color: #1e40af;
}

.badge-green {
  background: #dcfce7;
  color: #166534;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.permissions-section {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
}

.permissions-section h4 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.permissions-grid {
  display: grid;
  gap: 16px;
}

.permission-module {
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 12px;
  background: #f9fafb;
}

.module-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #e5e7eb;
}

.module-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.module-label {
  font-weight: 600;
  color: #1f2937;
  font-size: 14px;
  text-transform: capitalize;
  cursor: pointer;
}

.permission-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 8px;
}

.permission-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.permission-checkbox {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.permission-label {
  font-size: 13px;
  color: #4b5563;
  cursor: pointer;
}

.view-details {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-label {
  font-size: 13px;
  font-weight: 500;
  color: #6b7280;
}

.detail-value {
  font-size: 14px;
  color: #1f2937;
}

.detail-section h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 12px;
}

.detail-section p {
  color: #4b5563;
  line-height: 1.6;
  margin: 0;
}

.permission-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.permission-chip {
  padding: 6px 12px;
  background: #eff6ff;
  color: #1e40af;
  border-radius: 16px;
  font-size: 13px;
  font-weight: 500;
}
</style>
