<template>
  <AdminLayout :page-title="$t('customers.title')">
    <!-- Header with Create Button -->
    <div class="row mb-3">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
          <h3 class="mb-0">{{ $t('customers.customerManagement') }}</h3>
          <RouterLink 
            to="/customers/create" 
            class="btn btn-primary"
            v-if="canCreate"
          >
            <i class="fas fa-plus mr-2"></i>
            {{ $t('customers.createCustomer') }}
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col-md-6">
            <div class="input-group">
              <input
                type="text"
                class="form-control"
                :placeholder="$t('customers.searchPlaceholder')"
                v-model="searchQuery"
                @input="debouncedSearch"
              />
              <div class="input-group-append">
                <span class="input-group-text">
                  <i class="fas fa-search"></i>
                </span>
              </div>
            </div>
          </div>
          <div class="col-md-3">
            <select class="form-control" v-model="filters.status" @change="fetchCustomers">
              <option value="">{{ $t('customers.status') }}: {{ $t('common.all') || 'All' }}</option>
              <option value="active">{{ $t('customers.active') }}</option>
              <option value="inactive">{{ $t('customers.inactive') }}</option>
              <option value="blocked">{{ $t('customers.blocked') }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <select class="form-control" v-model="filters.type" @change="fetchCustomers">
              <option value="">{{ $t('customers.customerType') }}: {{ $t('common.all') || 'All' }}</option>
              <option value="individual">{{ $t('customers.individual') }}</option>
              <option value="business">{{ $t('customers.business') }}</option>
            </select>
          </div>
        </div>
      </div>

      <div class="card-body">
        <!-- Loading State -->
        <div v-if="customerStore.isLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">{{ $t('common.loading') }}</span>
          </div>
          <p class="mt-2">{{ $t('common.loading') }}</p>
        </div>

        <!-- Error State -->
        <Alert 
          v-else-if="customerStore.hasError" 
          type="danger" 
          :message="customerStore.error"
          @close="customerStore.clearError()"
        />

        <!-- Empty State -->
        <div v-else-if="!customerStore.hasCustomers" class="text-center py-5">
          <i class="fas fa-users fa-3x text-muted mb-3"></i>
          <p class="text-muted">{{ $t('customers.noCustomers') }}</p>
        </div>

        <!-- Data Table -->
        <div v-else class="table-responsive">
          <table class="table table-hover table-striped">
            <thead>
              <tr>
                <th>{{ $t('customers.customerNumber') }}</th>
                <th>{{ $t('customers.firstName') }}</th>
                <th>{{ $t('customers.lastName') }}</th>
                <th>{{ $t('customers.email') }}</th>
                <th>{{ $t('customers.phone') }}</th>
                <th>{{ $t('customers.customerType') }}</th>
                <th>{{ $t('customers.status') }}</th>
                <th>{{ $t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="customer in customerStore.customers" :key="customer.id">
                <td>
                  <RouterLink :to="`/customers/${customer.id}`" class="text-primary">
                    {{ customer.customer_number }}
                  </RouterLink>
                </td>
                <td>{{ customer.first_name }}</td>
                <td>{{ customer.last_name }}</td>
                <td>{{ customer.email || '-' }}</td>
                <td>{{ customer.phone || customer.mobile || '-' }}</td>
                <td>
                  <span class="badge" :class="{
                    'badge-info': customer.customer_type === 'individual',
                    'badge-primary': customer.customer_type === 'business'
                  }">
                    {{ $t(`customers.${customer.customer_type}`) }}
                  </span>
                </td>
                <td>
                  <span class="badge" :class="{
                    'badge-success': customer.status === 'active',
                    'badge-secondary': customer.status === 'inactive',
                    'badge-danger': customer.status === 'blocked'
                  }">
                    {{ $t(`customers.${customer.status}`) }}
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm" role="group">
                    <RouterLink 
                      :to="`/customers/${customer.id}`" 
                      class="btn btn-info"
                      :title="$t('customers.viewCustomer')"
                    >
                      <i class="fas fa-eye"></i>
                    </RouterLink>
                    <RouterLink 
                      v-if="canEdit"
                      :to="`/customers/${customer.id}/edit`" 
                      class="btn btn-warning"
                      :title="$t('customers.editCustomer')"
                    >
                      <i class="fas fa-edit"></i>
                    </RouterLink>
                    <button 
                      v-if="canDelete"
                      @click="confirmDelete(customer)" 
                      class="btn btn-danger"
                      :title="$t('customers.deleteCustomer')"
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
        <div v-if="customerStore.hasCustomers" class="row mt-3">
          <div class="col-md-6">
            <p class="text-muted">
              {{ $t('customers.showing', {
                from: ((customerStore.pagination.current_page - 1) * customerStore.pagination.per_page) + 1,
                to: Math.min(customerStore.pagination.current_page * customerStore.pagination.per_page, customerStore.pagination.total),
                total: customerStore.pagination.total
              }) }}
            </p>
          </div>
          <div class="col-md-3">
            <select class="form-control" v-model.number="perPage" @change="changePerPage">
              <option :value="10">10 {{ $t('users.perPage') }}</option>
              <option :value="25">25 {{ $t('users.perPage') }}</option>
              <option :value="50">50 {{ $t('users.perPage') }}</option>
              <option :value="100">100 {{ $t('users.perPage') }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <nav>
              <ul class="pagination justify-content-end mb-0">
                <li class="page-item" :class="{ disabled: customerStore.pagination.current_page === 1 }">
                  <button class="page-link" @click="goToPage(customerStore.pagination.current_page - 1)">
                    {{ $t('common.previous') || 'Previous' }}
                  </button>
                </li>
                <li class="page-item disabled">
                  <span class="page-link">
                    {{ customerStore.pagination.current_page }} / {{ customerStore.pagination.last_page }}
                  </span>
                </li>
                <li class="page-item" :class="{ disabled: customerStore.pagination.current_page === customerStore.pagination.last_page }">
                  <button class="page-link" @click="goToPage(customerStore.pagination.current_page + 1)">
                    {{ $t('common.next') || 'Next' }}
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm Delete Dialog -->
    <ConfirmDialog
      v-if="customerToDelete"
      :show="showDeleteDialog"
      :title="$t('customers.deleteCustomer')"
      :message="$t('customers.confirmDelete', { name: `${customerToDelete.first_name} ${customerToDelete.last_name}` })"
      @confirm="handleDelete"
      @cancel="cancelDelete"
    />

    <!-- Toast Notification -->
    <Toast ref="toast" />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCustomerStore } from '@/stores/customer';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Alert from '@/components/Alert.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Toast from '@/components/Toast.vue';

const router = useRouter();
const customerStore = useCustomerStore();
const authStore = useAuthStore();
const { t } = useI18n();

const toast = ref(null);
const searchQuery = ref('');
const perPage = ref(15);
const filters = ref({
  status: '',
  type: '',
});
const customerToDelete = ref(null);
const showDeleteDialog = ref(false);

// Permission checks
const canCreate = computed(() => {
  return authStore.hasPermission('customer.create') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

const canEdit = computed(() => {
  return authStore.hasPermission('customer.update') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

const canDelete = computed(() => {
  return authStore.hasPermission('customer.delete') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

// Debounced search
let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    if (searchQuery.value.length >= 2) {
      performSearch();
    } else if (searchQuery.value.length === 0) {
      fetchCustomers();
    }
  }, 500);
};

const performSearch = async () => {
  try {
    await customerStore.searchCustomers(searchQuery.value, {
      per_page: perPage.value,
      status: filters.value.status,
      type: filters.value.type,
    });
  } catch (error) {
    console.error('Search error:', error);
  }
};

const fetchCustomers = async () => {
  try {
    await customerStore.fetchCustomers({
      per_page: perPage.value,
      status: filters.value.status,
      type: filters.value.type,
    });
  } catch (error) {
    console.error('Fetch error:', error);
  }
};

const changePerPage = () => {
  fetchCustomers();
};

const goToPage = (page) => {
  if (page >= 1 && page <= customerStore.pagination.last_page) {
    const params = {
      page,
      per_page: perPage.value,
      status: filters.value.status,
      type: filters.value.type,
    };
    
    if (searchQuery.value) {
      customerStore.searchCustomers(searchQuery.value, params);
    } else {
      customerStore.fetchCustomers(params);
    }
  }
};

const confirmDelete = (customer) => {
  customerToDelete.value = customer;
  showDeleteDialog.value = true;
};

const cancelDelete = () => {
  customerToDelete.value = null;
  showDeleteDialog.value = false;
};

const handleDelete = async () => {
  try {
    await customerStore.deleteCustomer(customerToDelete.value.id);
    toast.value?.show(t('customers.deleteSuccess'), 'success');
    showDeleteDialog.value = false;
    customerToDelete.value = null;
  } catch (error) {
    toast.value?.show(t('customers.deleteFailed'), 'error');
  }
};

onMounted(() => {
  fetchCustomers();
});
</script>

<style scoped>
.table-responsive {
  overflow-x: auto;
}

@media (max-width: 768px) {
  .btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
  }
}
</style>
