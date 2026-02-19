<template>
  <AdminLayout :page-title="$t('users.title')">
    <!-- Header with Actions -->
    <div class="row mb-3">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
              <h3 class="card-title mb-0">
                <i class="fas fa-users mr-2"></i>{{ $t('users.userManagement') }}
              </h3>
              <button 
                class="btn btn-primary" 
                @click="openCreateModal"
                v-if="canCreate"
              >
                <i class="fas fa-plus mr-2"></i>{{ $t('users.createUser') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Users Table -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <div class="row">
              <div class="col-md-6">
                <div class="input-group">
                  <input 
                    type="text" 
                    class="form-control" 
                    :placeholder="$t('common.search')"
                    v-model="searchQuery"
                    @input="handleSearch"
                  >
                  <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button">
                      <i class="fas fa-search"></i>
                    </button>
                  </div>
                </div>
              </div>
              <div class="col-md-6 text-right">
                <select 
                  class="form-control" 
                  style="width: auto; display: inline-block;"
                  v-model="perPage"
                  @change="handlePerPageChange"
                >
                  <option :value="10">10 {{ $t('users.perPage') }}</option>
                  <option :value="25">25 {{ $t('users.perPage') }}</option>
                  <option :value="50">50 {{ $t('users.perPage') }}</option>
                  <option :value="100">100 {{ $t('users.perPage') }}</option>
                </select>
              </div>
            </div>
          </div>

          <div class="card-body table-responsive p-0">
            <!-- Loading State -->
            <div v-if="userStore.loading" class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="sr-only">{{ $t('common.loading') }}</span>
              </div>
            </div>

            <!-- Error State -->
            <div v-else-if="userStore.error" class="alert alert-danger m-3">
              {{ userStore.error }}
            </div>

            <!-- Empty State -->
            <div v-else-if="!userStore.hasUsers" class="text-center py-5">
              <i class="fas fa-users fa-3x text-muted mb-3"></i>
              <p class="text-muted">{{ $t('users.noUsers') }}</p>
            </div>

            <!-- Users Table -->
            <table v-else class="table table-hover text-nowrap">
              <thead>
                <tr>
                  <th>{{ $t('users.id') }}</th>
                  <th>{{ $t('users.name') }}</th>
                  <th>{{ $t('users.email') }}</th>
                  <th>{{ $t('users.roles') }}</th>
                  <th>{{ $t('users.status') }}</th>
                  <th>{{ $t('users.createdAt') }}</th>
                  <th class="text-right">{{ $t('common.actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in userStore.users" :key="user.id">
                  <td>{{ user.id }}</td>
                  <td>
                    <strong>{{ user.name }}</strong>
                  </td>
                  <td>{{ user.email }}</td>
                  <td>
                    <span 
                      v-for="role in user.roles" 
                      :key="role" 
                      class="badge badge-info mr-1"
                    >
                      {{ role }}
                    </span>
                    <span v-if="!user.roles || user.roles.length === 0" class="text-muted">
                      {{ $t('users.noRoles') }}
                    </span>
                  </td>
                  <td>
                    <span 
                      class="badge" 
                      :class="user.email_verified_at ? 'badge-success' : 'badge-warning'"
                    >
                      {{ user.email_verified_at ? $t('users.verified') : $t('users.unverified') }}
                    </span>
                  </td>
                  <td>{{ formatDate(user.created_at) }}</td>
                  <td class="text-right">
                    <div class="btn-group">
                      <button 
                        class="btn btn-sm btn-info" 
                        @click="viewUser(user)"
                        :title="$t('common.view')"
                      >
                        <i class="fas fa-eye"></i>
                      </button>
                      <button 
                        v-if="canEdit"
                        class="btn btn-sm btn-primary" 
                        @click="editUser(user)"
                        :title="$t('common.edit')"
                      >
                        <i class="fas fa-edit"></i>
                      </button>
                      <button 
                        v-if="canDelete"
                        class="btn btn-sm btn-danger" 
                        @click="confirmDeleteUser(user)"
                        :title="$t('common.delete')"
                      >
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="userStore.hasUsers" class="card-footer clearfix">
            <div class="row">
              <div class="col-md-6">
                <p class="text-muted mb-0">
                  {{ $t('users.showing', { 
                    from: ((userStore.pagination.currentPage - 1) * userStore.pagination.perPage) + 1,
                    to: Math.min(userStore.pagination.currentPage * userStore.pagination.perPage, userStore.pagination.total),
                    total: userStore.pagination.total
                  }) }}
                </p>
              </div>
              <div class="col-md-6">
                <ul class="pagination pagination-sm m-0 float-right">
                  <li class="page-item" :class="{ disabled: userStore.pagination.currentPage === 1 }">
                    <a class="page-link" href="#" @click.prevent="goToPage(userStore.pagination.currentPage - 1)">«</a>
                  </li>
                  <li 
                    v-for="page in visiblePages" 
                    :key="page"
                    class="page-item" 
                    :class="{ active: page === userStore.pagination.currentPage }"
                  >
                    <a class="page-link" href="#" @click.prevent="goToPage(page)">{{ page }}</a>
                  </li>
                  <li class="page-item" :class="{ disabled: userStore.pagination.currentPage === userStore.pagination.lastPage }">
                    <a class="page-link" href="#" @click.prevent="goToPage(userStore.pagination.currentPage + 1)">»</a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <ConfirmDialog
      :is-visible="showDeleteDialog"
      :title="$t('users.deleteUser')"
      :message="$t('users.confirmDelete', { name: userToDelete?.name || '' })"
      type="danger"
      :confirm-text="$t('common.delete')"
      :cancel-text="$t('common.cancel')"
      :loading="deleting"
      @confirm="handleDeleteConfirm"
      @cancel="showDeleteDialog = false"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '@/stores/user';
import { useAuthStore } from '@/stores/auth';
import { useToastStore } from '@/stores/toast';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/layouts/AdminLayout.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';

const router = useRouter();
const userStore = useUserStore();
const authStore = useAuthStore();
const toastStore = useToastStore();
const { t } = useI18n();

const searchQuery = ref('');
const perPage = ref(15);
const searchTimeout = ref(null);
const showDeleteDialog = ref(false);
const userToDelete = ref(null);
const deleting = ref(false);

// Permissions
const canCreate = computed(() => authStore.hasPermission('user.create'));
const canEdit = computed(() => authStore.hasPermission('user.update'));
const canDelete = computed(() => authStore.hasPermission('user.delete'));

// Computed visible pages for pagination
const visiblePages = computed(() => {
  const current = userStore.pagination.currentPage;
  const last = userStore.pagination.lastPage;
  const pages = [];
  const maxVisible = 5;

  let start = Math.max(1, current - Math.floor(maxVisible / 2));
  let end = Math.min(last, start + maxVisible - 1);

  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1);
  }

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  return pages;
});

// Fetch users on mount
onMounted(() => {
  fetchUsers();
});

// Methods
async function fetchUsers() {
  try {
    await userStore.fetchUsers({
      search: searchQuery.value || undefined,
    });
  } catch (error) {
    console.error('Failed to fetch users:', error);
  }
}

function handleSearch() {
  // Debounce search
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
  searchTimeout.value = setTimeout(() => {
    userStore.setPage(1);
    fetchUsers();
  }, 500);
}

function handlePerPageChange() {
  userStore.setPerPage(perPage.value);
  fetchUsers();
}

function goToPage(page) {
  if (page < 1 || page > userStore.pagination.lastPage) return;
  userStore.setPage(page);
  fetchUsers();
}

function formatDate(date) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString();
}

function viewUser(user) {
  router.push(`/users/${user.id}`);
}

function editUser(user) {
  router.push(`/users/${user.id}/edit`);
}

function openCreateModal() {
  router.push('/users/create');
}

function confirmDeleteUser(user) {
  userToDelete.value = user;
  showDeleteDialog.value = true;
}

async function handleDeleteConfirm() {
  if (!userToDelete.value) return;
  
  deleting.value = true;
  try {
    await userStore.deleteUser(userToDelete.value.id);
    toastStore.success(t('users.deleteSuccess'));
    showDeleteDialog.value = false;
    userToDelete.value = null;
  } catch (error) {
    toastStore.error(error.response?.data?.message || t('users.deleteFailed'));
  } finally {
    deleting.value = false;
  }
}
</script>

<style scoped>
.table th {
  border-top: 0;
}

.badge {
  font-size: 0.875rem;
}
</style>
