<template>
  <AdminLayout :page-title="$t('serviceRecords.title')">
    <!-- Header with Create Button -->
    <div class="row mb-3">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
          <h3 class="mb-0">{{ $t('serviceRecords.serviceRecordManagement') }}</h3>
          <RouterLink 
            to="/service-records/create" 
            class="btn btn-primary"
            v-if="canCreate"
          >
            <i class="fas fa-plus mr-2"></i>
            {{ $t('serviceRecords.createServiceRecord') }}
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col-md-4">
            <div class="input-group">
              <input
                type="text"
                class="form-control"
                :placeholder="$t('serviceRecords.searchPlaceholder')"
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
          <div class="col-md-2">
            <select class="form-control" v-model="filters.status" @change="fetchServiceRecords">
              <option value="">{{ $t('serviceRecords.status') }}: {{ $t('common.all') || 'All' }}</option>
              <option value="pending">{{ $t('serviceRecords.pending') }}</option>
              <option value="in_progress">{{ $t('serviceRecords.inProgress') }}</option>
              <option value="completed">{{ $t('serviceRecords.completed') }}</option>
              <option value="cancelled">{{ $t('serviceRecords.cancelled') }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-control" v-model="filters.service_type" @change="fetchServiceRecords">
              <option value="">{{ $t('serviceRecords.serviceType') }}: {{ $t('common.all') || 'All' }}</option>
              <option value="oil_change">{{ $t('serviceRecords.oilChange') }}</option>
              <option value="tire_rotation">{{ $t('serviceRecords.tireRotation') }}</option>
              <option value="brake_service">{{ $t('serviceRecords.brakeService') }}</option>
              <option value="engine_tuneup">{{ $t('serviceRecords.engineTuneup') }}</option>
              <option value="transmission">{{ $t('serviceRecords.transmission') }}</option>
              <option value="inspection">{{ $t('serviceRecords.inspection') }}</option>
              <option value="repair">{{ $t('serviceRecords.repair') }}</option>
              <option value="maintenance">{{ $t('serviceRecords.maintenance') }}</option>
              <option value="diagnostic">{{ $t('serviceRecords.diagnostic') }}</option>
              <option value="other">{{ $t('serviceRecords.other') }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-control" v-model="filters.branch_id" @change="fetchServiceRecords">
              <option value="">{{ $t('serviceRecords.branch') }}: {{ $t('common.all') || 'All' }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <select class="form-control" v-model.number="perPage" @change="changePerPage">
              <option :value="10">10 {{ $t('users.perPage') }}</option>
              <option :value="25">25 {{ $t('users.perPage') }}</option>
              <option :value="50">50 {{ $t('users.perPage') }}</option>
              <option :value="100">100 {{ $t('users.perPage') }}</option>
            </select>
          </div>
        </div>
      </div>

      <div class="card-body">
        <!-- Loading State -->
        <div v-if="serviceRecordStore.isLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">{{ $t('common.loading') }}</span>
          </div>
          <p class="mt-2">{{ $t('common.loading') }}</p>
        </div>

        <!-- Error State -->
        <Alert 
          v-else-if="serviceRecordStore.hasError" 
          type="danger" 
          :message="serviceRecordStore.error"
          @close="serviceRecordStore.clearError()"
        />

        <!-- Empty State -->
        <div v-else-if="!serviceRecordStore.hasServiceRecords" class="text-center py-5">
          <i class="fas fa-wrench fa-3x text-muted mb-3"></i>
          <p class="text-muted">{{ $t('serviceRecords.noServiceRecords') }}</p>
        </div>

        <!-- Data Table -->
        <div v-else class="table-responsive">
          <table class="table table-hover table-striped">
            <thead>
              <tr>
                <th>{{ $t('serviceRecords.serviceNumber') }}</th>
                <th>{{ $t('serviceRecords.serviceDate') }}</th>
                <th>{{ $t('serviceRecords.vehicle') }}</th>
                <th>{{ $t('serviceRecords.customer') }}</th>
                <th>{{ $t('serviceRecords.serviceType') }}</th>
                <th>{{ $t('serviceRecords.status') }}</th>
                <th>{{ $t('serviceRecords.totalCost') }}</th>
                <th>{{ $t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="record in serviceRecordStore.serviceRecords" :key="record.id">
                <td>
                  <RouterLink :to="`/service-records/${record.id}`" class="text-primary">
                    {{ record.service_number }}
                  </RouterLink>
                </td>
                <td>{{ formatDate(record.service_date) }}</td>
                <td>
                  <RouterLink 
                    v-if="record.vehicle"
                    :to="`/vehicles/${record.vehicle_id}`" 
                    class="text-primary"
                  >
                    {{ record.vehicle?.make }} {{ record.vehicle?.model }} ({{ record.vehicle?.registration_number }})
                  </RouterLink>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>
                  <RouterLink 
                    v-if="record.customer"
                    :to="`/customers/${record.customer_id}`" 
                    class="text-primary"
                  >
                    {{ record.customer?.first_name }} {{ record.customer?.last_name }}
                  </RouterLink>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>{{ record.service_type }}</td>
                <td>
                  <span class="badge" :class="{
                    'badge-warning': record.status === 'pending',
                    'badge-info': record.status === 'in_progress',
                    'badge-success': record.status === 'completed',
                    'badge-danger': record.status === 'cancelled'
                  }">
                    {{ $t(`serviceRecords.${record.status}`) }}
                  </span>
                </td>
                <td>{{ formatCurrency(record.total_cost) }}</td>
                <td>
                  <div class="btn-group btn-group-sm" role="group">
                    <RouterLink 
                      :to="`/service-records/${record.id}`" 
                      class="btn btn-info"
                      :title="$t('serviceRecords.viewServiceRecord')"
                    >
                      <i class="fas fa-eye"></i>
                    </RouterLink>
                    <RouterLink 
                      v-if="canEdit && record.status !== 'completed' && record.status !== 'cancelled'"
                      :to="`/service-records/${record.id}/edit`" 
                      class="btn btn-warning"
                      :title="$t('serviceRecords.editServiceRecord')"
                    >
                      <i class="fas fa-edit"></i>
                    </RouterLink>
                    <button 
                      v-if="canEdit && record.status === 'in_progress'"
                      @click="confirmComplete(record)" 
                      class="btn btn-success"
                      :title="$t('serviceRecords.complete')"
                    >
                      <i class="fas fa-check"></i>
                    </button>
                    <button 
                      v-if="canEdit && (record.status === 'pending' || record.status === 'in_progress')"
                      @click="confirmCancel(record)" 
                      class="btn btn-secondary"
                      :title="$t('serviceRecords.cancel')"
                    >
                      <i class="fas fa-ban"></i>
                    </button>
                    <button 
                      v-if="canDelete"
                      @click="confirmDelete(record)" 
                      class="btn btn-danger"
                      :title="$t('serviceRecords.deleteServiceRecord')"
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
        <div v-if="serviceRecordStore.hasServiceRecords" class="row mt-3">
          <div class="col-md-6">
            <p class="text-muted">
              {{ $t('serviceRecords.showing', {
                from: ((serviceRecordStore.pagination.current_page - 1) * serviceRecordStore.pagination.per_page) + 1,
                to: Math.min(serviceRecordStore.pagination.current_page * serviceRecordStore.pagination.per_page, serviceRecordStore.pagination.total),
                total: serviceRecordStore.pagination.total
              }) }}
            </p>
          </div>
          <div class="col-md-6">
            <nav>
              <ul class="pagination justify-content-end mb-0">
                <li class="page-item" :class="{ disabled: serviceRecordStore.pagination.current_page === 1 }">
                  <button class="page-link" @click="goToPage(serviceRecordStore.pagination.current_page - 1)">
                    {{ $t('common.previous') || 'Previous' }}
                  </button>
                </li>
                <li class="page-item disabled">
                  <span class="page-link">
                    {{ serviceRecordStore.pagination.current_page }} / {{ serviceRecordStore.pagination.last_page }}
                  </span>
                </li>
                <li class="page-item" :class="{ disabled: serviceRecordStore.pagination.current_page === serviceRecordStore.pagination.last_page }">
                  <button class="page-link" @click="goToPage(serviceRecordStore.pagination.current_page + 1)">
                    {{ $t('common.next') || 'Next' }}
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm Complete Dialog -->
    <ConfirmDialog
      v-if="recordToComplete"
      :show="showCompleteDialog"
      :title="$t('serviceRecords.complete')"
      :message="$t('serviceRecords.confirmComplete')"
      @confirm="handleComplete"
      @cancel="cancelComplete"
    />

    <!-- Confirm Cancel Dialog -->
    <ConfirmDialog
      v-if="recordToCancel"
      :show="showCancelDialog"
      :title="$t('serviceRecords.cancel')"
      :message="$t('serviceRecords.confirmCancel')"
      @confirm="handleCancel"
      @cancel="cancelCancelAction"
    />

    <!-- Confirm Delete Dialog -->
    <ConfirmDialog
      v-if="recordToDelete"
      :show="showDeleteDialog"
      :title="$t('serviceRecords.deleteServiceRecord')"
      :message="$t('serviceRecords.confirmDelete')"
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
import { useServiceRecordStore } from '@/stores/serviceRecord';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Alert from '@/components/Alert.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Toast from '@/components/Toast.vue';

const router = useRouter();
const serviceRecordStore = useServiceRecordStore();
const authStore = useAuthStore();
const { t } = useI18n();

const toast = ref(null);
const searchQuery = ref('');
const perPage = ref(15);
const filters = ref({
  status: '',
  service_type: '',
  branch_id: '',
});
const recordToDelete = ref(null);
const recordToComplete = ref(null);
const recordToCancel = ref(null);
const showDeleteDialog = ref(false);
const showCompleteDialog = ref(false);
const showCancelDialog = ref(false);

// Permission checks
const canCreate = computed(() => {
  return authStore.hasPermission('service-record.create') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

const canEdit = computed(() => {
  return authStore.hasPermission('service-record.update') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

const canDelete = computed(() => {
  return authStore.hasPermission('service-record.delete') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

// Debounced search
let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    if (searchQuery.value.length >= 2) {
      performSearch();
    } else if (searchQuery.value.length === 0) {
      fetchServiceRecords();
    }
  }, 500);
};

const performSearch = async () => {
  try {
    await serviceRecordStore.searchServiceRecords(searchQuery.value, {
      per_page: perPage.value,
      status: filters.value.status,
      service_type: filters.value.service_type,
      branch_id: filters.value.branch_id,
    });
  } catch (error) {
    console.error('Search error:', error);
  }
};

const fetchServiceRecords = async () => {
  try {
    await serviceRecordStore.fetchServiceRecords({
      per_page: perPage.value,
      status: filters.value.status,
      service_type: filters.value.service_type,
      branch_id: filters.value.branch_id,
    });
  } catch (error) {
    console.error('Fetch error:', error);
  }
};

const changePerPage = () => {
  fetchServiceRecords();
};

const goToPage = (page) => {
  if (page >= 1 && page <= serviceRecordStore.pagination.last_page) {
    const params = {
      page,
      per_page: perPage.value,
      status: filters.value.status,
      service_type: filters.value.service_type,
      branch_id: filters.value.branch_id,
    };
    
    if (searchQuery.value) {
      serviceRecordStore.searchServiceRecords(searchQuery.value, params);
    } else {
      serviceRecordStore.fetchServiceRecords(params);
    }
  }
};

const confirmComplete = (record) => {
  recordToComplete.value = record;
  showCompleteDialog.value = true;
};

const cancelComplete = () => {
  recordToComplete.value = null;
  showCompleteDialog.value = false;
};

const handleComplete = async () => {
  try {
    await serviceRecordStore.completeServiceRecord(recordToComplete.value.id);
    toast.value?.show(t('serviceRecords.completeSuccess'), 'success');
    showCompleteDialog.value = false;
    recordToComplete.value = null;
  } catch (error) {
    toast.value?.show(t('serviceRecords.completeFailed'), 'error');
  }
};

const confirmCancel = (record) => {
  recordToCancel.value = record;
  showCancelDialog.value = true;
};

const cancelCancelAction = () => {
  recordToCancel.value = null;
  showCancelDialog.value = false;
};

const handleCancel = async () => {
  try {
    await serviceRecordStore.cancelServiceRecord(recordToCancel.value.id);
    toast.value?.show(t('serviceRecords.cancelSuccess'), 'success');
    showCancelDialog.value = false;
    recordToCancel.value = null;
  } catch (error) {
    toast.value?.show(t('serviceRecords.cancelFailed'), 'error');
  }
};

const confirmDelete = (record) => {
  recordToDelete.value = record;
  showDeleteDialog.value = true;
};

const cancelDelete = () => {
  recordToDelete.value = null;
  showDeleteDialog.value = false;
};

const handleDelete = async () => {
  try {
    await serviceRecordStore.deleteServiceRecord(recordToDelete.value.id);
    toast.value?.show(t('serviceRecords.deleteSuccess'), 'success');
    showDeleteDialog.value = false;
    recordToDelete.value = null;
  } catch (error) {
    toast.value?.show(t('serviceRecords.deleteFailed'), 'error');
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString();
};

onMounted(() => {
  fetchServiceRecords();
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
