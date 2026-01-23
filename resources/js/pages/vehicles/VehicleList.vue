<template>
  <AdminLayout :page-title="$t('vehicles.title')">
    <!-- Header with Create Button -->
    <div class="row mb-3">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
          <h3 class="mb-0">{{ $t('vehicles.vehicleManagement') }}</h3>
          <RouterLink 
            to="/vehicles/create" 
            class="btn btn-primary"
            v-if="canCreate"
          >
            <i class="fas fa-plus mr-2"></i>
            {{ $t('vehicles.createVehicle') }}
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
                :placeholder="$t('vehicles.searchPlaceholder')"
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
            <select class="form-control" v-model="filters.status" @change="fetchVehicles">
              <option value="">{{ $t('vehicles.status') }}: {{ $t('common.all') || 'All' }}</option>
              <option value="active">{{ $t('vehicles.active') }}</option>
              <option value="inactive">{{ $t('vehicles.inactive') }}</option>
              <option value="sold">{{ $t('vehicles.sold') }}</option>
              <option value="scrapped">{{ $t('vehicles.scrapped') }}</option>
            </select>
          </div>
          <div class="col-md-3">
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
        <div v-if="vehicleStore.isLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="sr-only">{{ $t('common.loading') }}</span>
          </div>
          <p class="mt-2">{{ $t('common.loading') }}</p>
        </div>

        <!-- Error State -->
        <Alert 
          v-else-if="vehicleStore.hasError" 
          type="danger" 
          :message="vehicleStore.error"
          @close="vehicleStore.clearError()"
        />

        <!-- Empty State -->
        <div v-else-if="!vehicleStore.hasVehicles" class="text-center py-5">
          <i class="fas fa-car fa-3x text-muted mb-3"></i>
          <p class="text-muted">{{ $t('vehicles.noVehicles') }}</p>
        </div>

        <!-- Data Table -->
        <div v-else class="table-responsive">
          <table class="table table-hover table-striped">
            <thead>
              <tr>
                <th>{{ $t('vehicles.vehicleNumber') }}</th>
                <th>{{ $t('vehicles.registrationNumber') }}</th>
                <th>{{ $t('vehicles.make') }}</th>
                <th>{{ $t('vehicles.model') }}</th>
                <th>{{ $t('vehicles.year') }}</th>
                <th>{{ $t('vehicles.owner') }}</th>
                <th>{{ $t('vehicles.mileage') }}</th>
                <th>{{ $t('vehicles.status') }}</th>
                <th>{{ $t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="vehicle in vehicleStore.vehicles" :key="vehicle.id">
                <td>
                  <RouterLink :to="`/vehicles/${vehicle.id}`" class="text-primary">
                    {{ vehicle.vehicle_number }}
                  </RouterLink>
                </td>
                <td>{{ vehicle.registration_number }}</td>
                <td>{{ vehicle.make }}</td>
                <td>{{ vehicle.model }}</td>
                <td>{{ vehicle.year }}</td>
                <td>
                  <RouterLink 
                    v-if="vehicle.customer"
                    :to="`/customers/${vehicle.customer_id}`" 
                    class="text-primary"
                  >
                    {{ vehicle.customer?.first_name }} {{ vehicle.customer?.last_name }}
                  </RouterLink>
                  <span v-else class="text-muted">-</span>
                </td>
                <td>{{ formatNumber(vehicle.current_mileage) }} km</td>
                <td>
                  <span class="badge" :class="{
                    'badge-success': vehicle.status === 'active',
                    'badge-secondary': vehicle.status === 'inactive',
                    'badge-warning': vehicle.status === 'sold',
                    'badge-danger': vehicle.status === 'scrapped'
                  }">
                    {{ $t(`vehicles.${vehicle.status}`) }}
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm" role="group">
                    <RouterLink 
                      :to="`/vehicles/${vehicle.id}`" 
                      class="btn btn-info"
                      :title="$t('vehicles.viewVehicle')"
                    >
                      <i class="fas fa-eye"></i>
                    </RouterLink>
                    <RouterLink 
                      v-if="canEdit"
                      :to="`/vehicles/${vehicle.id}/edit`" 
                      class="btn btn-warning"
                      :title="$t('vehicles.editVehicle')"
                    >
                      <i class="fas fa-edit"></i>
                    </RouterLink>
                    <button 
                      v-if="canDelete"
                      @click="confirmDelete(vehicle)" 
                      class="btn btn-danger"
                      :title="$t('vehicles.deleteVehicle')"
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
        <div v-if="vehicleStore.hasVehicles" class="row mt-3">
          <div class="col-md-6">
            <p class="text-muted">
              {{ $t('vehicles.showing', {
                from: ((vehicleStore.pagination.current_page - 1) * vehicleStore.pagination.per_page) + 1,
                to: Math.min(vehicleStore.pagination.current_page * vehicleStore.pagination.per_page, vehicleStore.pagination.total),
                total: vehicleStore.pagination.total
              }) }}
            </p>
          </div>
          <div class="col-md-6">
            <nav>
              <ul class="pagination justify-content-end mb-0">
                <li class="page-item" :class="{ disabled: vehicleStore.pagination.current_page === 1 }">
                  <button class="page-link" @click="goToPage(vehicleStore.pagination.current_page - 1)">
                    {{ $t('common.previous') || 'Previous' }}
                  </button>
                </li>
                <li class="page-item disabled">
                  <span class="page-link">
                    {{ vehicleStore.pagination.current_page }} / {{ vehicleStore.pagination.last_page }}
                  </span>
                </li>
                <li class="page-item" :class="{ disabled: vehicleStore.pagination.current_page === vehicleStore.pagination.last_page }">
                  <button class="page-link" @click="goToPage(vehicleStore.pagination.current_page + 1)">
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
      v-if="vehicleToDelete"
      :show="showDeleteDialog"
      :title="$t('vehicles.deleteVehicle')"
      :message="$t('vehicles.confirmDelete', { name: `${vehicleToDelete.make} ${vehicleToDelete.model} (${vehicleToDelete.registration_number})` })"
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
import { useVehicleStore } from '@/stores/vehicle';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Alert from '@/components/Alert.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Toast from '@/components/Toast.vue';

const router = useRouter();
const vehicleStore = useVehicleStore();
const authStore = useAuthStore();
const { t } = useI18n();

const toast = ref(null);
const searchQuery = ref('');
const perPage = ref(15);
const filters = ref({
  status: '',
});
const vehicleToDelete = ref(null);
const showDeleteDialog = ref(false);

// Permission checks
const canCreate = computed(() => {
  return authStore.hasPermission('vehicle.create') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

const canEdit = computed(() => {
  return authStore.hasPermission('vehicle.update') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

const canDelete = computed(() => {
  return authStore.hasPermission('vehicle.delete') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

// Debounced search
let searchTimeout;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    if (searchQuery.value.length >= 2) {
      performSearch();
    } else if (searchQuery.value.length === 0) {
      fetchVehicles();
    }
  }, 500);
};

const performSearch = async () => {
  try {
    await vehicleStore.searchVehicles(searchQuery.value, {
      per_page: perPage.value,
      status: filters.value.status,
    });
  } catch (error) {
    console.error('Search error:', error);
  }
};

const fetchVehicles = async () => {
  try {
    await vehicleStore.fetchVehicles({
      per_page: perPage.value,
      status: filters.value.status,
    });
  } catch (error) {
    console.error('Fetch error:', error);
  }
};

const changePerPage = () => {
  fetchVehicles();
};

const goToPage = (page) => {
  if (page >= 1 && page <= vehicleStore.pagination.last_page) {
    const params = {
      page,
      per_page: perPage.value,
      status: filters.value.status,
    };
    
    if (searchQuery.value) {
      vehicleStore.searchVehicles(searchQuery.value, params);
    } else {
      vehicleStore.fetchVehicles(params);
    }
  }
};

const confirmDelete = (vehicle) => {
  vehicleToDelete.value = vehicle;
  showDeleteDialog.value = true;
};

const cancelDelete = () => {
  vehicleToDelete.value = null;
  showDeleteDialog.value = false;
};

const handleDelete = async () => {
  try {
    await vehicleStore.deleteVehicle(vehicleToDelete.value.id);
    toast.value?.show(t('vehicles.deleteSuccess'), 'success');
    showDeleteDialog.value = false;
    vehicleToDelete.value = null;
  } catch (error) {
    toast.value?.show(t('vehicles.deleteFailed'), 'error');
  }
};

const formatNumber = (value) => {
  return new Intl.NumberFormat('en-US').format(value);
};

onMounted(() => {
  fetchVehicles();
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
