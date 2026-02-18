<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Users</h1>
      <button @click="showCreateModal = true" class="btn btn-primary">
        <PlusIcon class="icon" />
        Add User
      </button>
    </div>
    
    <DataTable
      :columns="columns"
      :data="users"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-status="{ row }">
        <span :class="['badge', row.status === 'active' ? 'badge-success' : 'badge-danger']">
          {{ row.status }}
        </span>
      </template>
      
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="editUser(row)" class="btn-icon" title="Edit">
            <PencilIcon class="icon" />
          </button>
          <button @click="deleteUserConfirm(row)" class="btn-icon" title="Delete">
            <TrashIcon class="icon" />
          </button>
        </div>
      </template>
    </DataTable>
    
    <Modal
      :show="showCreateModal"
      title="Add User"
      @close="showCreateModal = false"
      @confirm="handleCreateUser"
    >
      <FormInput v-model="form.name" label="Name" required />
      <FormInput v-model="form.email" label="Email" type="email" required />
      <FormInput v-model="form.password" label="Password" type="password" required />
      <FormSelect
        v-model="form.role"
        label="Role"
        :options="roleOptions"
        required
      />
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import { useIAMStore } from '../../../stores/iamStore'

const iamStore = useIAMStore()

const showCreateModal = ref(false)
const currentPage = ref(1)
const totalPages = ref(1)
const form = ref({
  name: '',
  email: '',
  password: '',
  role: ''
})

const columns = [
  { key: 'id', label: 'ID', width: '80px' },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'role', label: 'Role' },
  { key: 'status', label: 'Status' }
]

const users = computed(() => iamStore.users)
const loading = computed(() => iamStore.loading)

const roleOptions = [
  { value: 'admin', label: 'Administrator' },
  { value: 'manager', label: 'Manager' },
  { value: 'user', label: 'User' }
]

onMounted(() => {
  fetchUsers()
})

const fetchUsers = async () => {
  await iamStore.fetchUsers({ page: currentPage.value })
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchUsers()
}

const handleCreateUser = async () => {
  await iamStore.createUser(form.value)
  showCreateModal.value = false
  form.value = { name: '', email: '', password: '', role: '' }
}

const editUser = (user) => {
  console.log('Edit user:', user)
}

const deleteUserConfirm = async (user) => {
  if (confirm(`Are you sure you want to delete user ${user.name}?`)) {
    await iamStore.deleteUser(user.id)
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

.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.badge-success {
  background: #dcfce7;
  color: #166534;
}

.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}
</style>
