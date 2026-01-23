<template>
  <AdminLayout :page-title="$t('customers.customerDetails')">
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

    <!-- Customer Details -->
    <div v-else-if="customer">
      <!-- Header with Actions -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center">
            <h3 class="mb-0">
              {{ customer.first_name }} {{ customer.last_name }}
              <span class="badge ml-2" :class="{
                'badge-success': customer.status === 'active',
                'badge-secondary': customer.status === 'inactive',
                'badge-danger': customer.status === 'blocked'
              }">
                {{ $t(`customers.${customer.status}`) }}
              </span>
            </h3>
            <div class="btn-group" role="group">
              <button 
                @click="goBack" 
                class="btn btn-secondary"
                :title="$t('common.back')"
              >
                <i class="fas fa-arrow-left mr-2"></i>
                {{ $t('common.back') }}
              </button>
              <RouterLink 
                v-if="canEdit"
                :to="`/customers/${customer.id}/edit`" 
                class="btn btn-warning"
                :title="$t('customers.editCustomer')"
              >
                <i class="fas fa-edit mr-2"></i>
                {{ $t('common.edit') }}
              </RouterLink>
              <button 
                v-if="canDelete"
                @click="confirmDelete" 
                class="btn btn-danger"
                :title="$t('customers.deleteCustomer')"
              >
                <i class="fas fa-trash mr-2"></i>
                {{ $t('common.delete') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="row mb-3" v-if="statistics">
        <div class="col-lg-3 col-6">
          <div class="small-box bg-info">
            <div class="inner">
              <h3>{{ statistics.total_vehicles || 0 }}</h3>
              <p>{{ $t('customers.totalVehicles') }}</p>
            </div>
            <div class="icon">
              <i class="fas fa-car"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-success">
            <div class="inner">
              <h3>{{ statistics.total_service_records || 0 }}</h3>
              <p>Total Services</p>
            </div>
            <div class="icon">
              <i class="fas fa-wrench"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3>{{ formatCurrency(statistics.total_spent || 0) }}</h3>
              <p>Total Spent</p>
            </div>
            <div class="icon">
              <i class="fas fa-dollar-sign"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-danger">
            <div class="inner">
              <h3>{{ statistics.active_vehicles || 0 }}</h3>
              <p>Active Vehicles</p>
            </div>
            <div class="icon">
              <i class="fas fa-check-circle"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Customer Information Card -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-user mr-2"></i>
                {{ $t('customers.customerDetails') }}
              </h3>
            </div>
            <div class="card-body">
              <dl class="row mb-0">
                <dt class="col-sm-4">{{ $t('customers.customerNumber') }}:</dt>
                <dd class="col-sm-8">{{ customer.customer_number }}</dd>

                <dt class="col-sm-4">{{ $t('customers.firstName') }}:</dt>
                <dd class="col-sm-8">{{ customer.first_name }}</dd>

                <dt class="col-sm-4">{{ $t('customers.lastName') }}:</dt>
                <dd class="col-sm-8">{{ customer.last_name }}</dd>

                <dt class="col-sm-4">{{ $t('customers.customerType') }}:</dt>
                <dd class="col-sm-8">
                  <span class="badge" :class="{
                    'badge-info': customer.customer_type === 'individual',
                    'badge-primary': customer.customer_type === 'business'
                  }">
                    {{ $t(`customers.${customer.customer_type}`) }}
                  </span>
                </dd>

                <dt class="col-sm-4" v-if="customer.business_name">{{ $t('customers.businessName') }}:</dt>
                <dd class="col-sm-8" v-if="customer.business_name">{{ customer.business_name }}</dd>

                <dt class="col-sm-4" v-if="customer.tax_id">{{ $t('customers.taxId') }}:</dt>
                <dd class="col-sm-8" v-if="customer.tax_id">{{ customer.tax_id }}</dd>

                <dt class="col-sm-4">{{ $t('customers.email') }}:</dt>
                <dd class="col-sm-8">{{ customer.email || '-' }}</dd>

                <dt class="col-sm-4">{{ $t('customers.phone') }}:</dt>
                <dd class="col-sm-8">{{ customer.phone || '-' }}</dd>

                <dt class="col-sm-4">{{ $t('customers.mobile') }}:</dt>
                <dd class="col-sm-8">{{ customer.mobile || '-' }}</dd>
              </dl>
            </div>
          </div>
        </div>

        <!-- Address Information Card -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-map-marker-alt mr-2"></i>
                {{ $t('customers.address') }}
              </h3>
            </div>
            <div class="card-body">
              <dl class="row mb-0">
                <dt class="col-sm-4">{{ $t('customers.address') }}:</dt>
                <dd class="col-sm-8">{{ customer.address || '-' }}</dd>

                <dt class="col-sm-4">{{ $t('customers.city') }}:</dt>
                <dd class="col-sm-8">{{ customer.city || '-' }}</dd>

                <dt class="col-sm-4">{{ $t('customers.state') }}:</dt>
                <dd class="col-sm-8">{{ customer.state || '-' }}</dd>

                <dt class="col-sm-4">{{ $t('customers.postalCode') }}:</dt>
                <dd class="col-sm-8">{{ customer.postal_code || '-' }}</dd>

                <dt class="col-sm-4">{{ $t('customers.country') }}:</dt>
                <dd class="col-sm-8">{{ customer.country || '-' }}</dd>
              </dl>
            </div>
          </div>

          <!-- Notes Card -->
          <div class="card" v-if="customer.notes">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-sticky-note mr-2"></i>
                {{ $t('customers.notes') }}
              </h3>
            </div>
            <div class="card-body">
              <p class="mb-0">{{ customer.notes }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Vehicles List -->
      <div class="row mt-3">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                  <i class="fas fa-car mr-2"></i>
                  {{ $t('customers.vehicles') }}
                </h3>
              </div>
            </div>
            <div class="card-body">
              <!-- Empty State -->
              <div v-if="!customer.vehicles || customer.vehicles.length === 0" class="text-center py-5">
                <i class="fas fa-car fa-3x text-muted mb-3"></i>
                <p class="text-muted">{{ $t('customers.noVehicles') }}</p>
              </div>

              <!-- Vehicles Table -->
              <div v-else class="table-responsive">
                <table class="table table-hover table-striped">
                  <thead>
                    <tr>
                      <th>{{ $t('vehicles.vehicleNumber') }}</th>
                      <th>{{ $t('vehicles.registrationNumber') }}</th>
                      <th>{{ $t('vehicles.make') }}</th>
                      <th>{{ $t('vehicles.model') }}</th>
                      <th>{{ $t('vehicles.year') }}</th>
                      <th>{{ $t('vehicles.mileage') }}</th>
                      <th>{{ $t('vehicles.status') }}</th>
                      <th>{{ $t('common.actions') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="vehicle in customer.vehicles" :key="vehicle.id">
                      <td>
                        <RouterLink :to="`/vehicles/${vehicle.id}`" class="text-primary">
                          {{ vehicle.vehicle_number }}
                        </RouterLink>
                      </td>
                      <td>{{ vehicle.registration_number }}</td>
                      <td>{{ vehicle.make }}</td>
                      <td>{{ vehicle.model }}</td>
                      <td>{{ vehicle.year }}</td>
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
                        <RouterLink 
                          :to="`/vehicles/${vehicle.id}`" 
                          class="btn btn-sm btn-info"
                          :title="$t('vehicles.viewVehicle')"
                        >
                          <i class="fas fa-eye"></i>
                        </RouterLink>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm Delete Dialog -->
    <ConfirmDialog
      v-if="showDeleteDialog"
      :show="showDeleteDialog"
      :title="$t('customers.deleteCustomer')"
      :message="$t('customers.confirmDelete', { name: `${customer?.first_name} ${customer?.last_name}` })"
      @confirm="handleDelete"
      @cancel="showDeleteDialog = false"
    />

    <!-- Toast Notification -->
    <Toast ref="toast" />
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useCustomerStore } from '@/stores/customer';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Alert from '@/components/Alert.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Toast from '@/components/Toast.vue';

const router = useRouter();
const route = useRoute();
const customerStore = useCustomerStore();
const authStore = useAuthStore();
const { t } = useI18n();

const toast = ref(null);
const customer = ref(null);
const statistics = ref(null);
const showDeleteDialog = ref(false);

// Permission checks
const canEdit = computed(() => {
  return authStore.hasPermission('customer.update') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

const canDelete = computed(() => {
  return authStore.hasPermission('customer.delete') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

const fetchCustomerData = async () => {
  try {
    const customerId = route.params.id;
    const [customerData, statsData] = await Promise.all([
      customerStore.fetchCustomerWithVehicles(customerId),
      customerStore.fetchCustomerStatistics(customerId).catch(() => null)
    ]);
    customer.value = customerData;
    statistics.value = statsData;
  } catch (error) {
    console.error('Failed to fetch customer:', error);
    toast.value?.show(t('errors.generic'), 'error');
  }
};

const confirmDelete = () => {
  showDeleteDialog.value = true;
};

const handleDelete = async () => {
  try {
    await customerStore.deleteCustomer(customer.value.id);
    toast.value?.show(t('customers.deleteSuccess'), 'success');
    showDeleteDialog.value = false;
    router.push('/customers');
  } catch (error) {
    toast.value?.show(t('customers.deleteFailed'), 'error');
  }
};

const goBack = () => {
  router.push('/customers');
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(value);
};

const formatNumber = (value) => {
  return new Intl.NumberFormat('en-US').format(value);
};

onMounted(() => {
  fetchCustomerData();
});
</script>

<style scoped>
.small-box {
  border-radius: 0.25rem;
  position: relative;
  display: block;
  margin-bottom: 20px;
  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
}

.small-box > .inner {
  padding: 10px;
}

.small-box h3 {
  font-size: 2.2rem;
  font-weight: 700;
  margin: 0 0 10px 0;
  padding: 0;
  white-space: nowrap;
  color: #fff;
}

.small-box p {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.8);
  margin: 0;
}

.small-box .icon {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 0;
  font-size: 70px;
  color: rgba(0, 0, 0, 0.15);
}

dl.row dt {
  font-weight: 600;
}

dl.row dd {
  margin-bottom: 0.5rem;
}
</style>
