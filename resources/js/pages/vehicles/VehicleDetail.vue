<template>
  <AdminLayout :page-title="$t('vehicles.vehicleDetails')">
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

    <!-- Vehicle Details -->
    <div v-else-if="vehicle">
      <!-- Header with Actions -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center">
            <h3 class="mb-0">
              {{ vehicle.make }} {{ vehicle.model }} ({{ vehicle.year }})
              <span class="badge ml-2" :class="{
                'badge-success': vehicle.status === 'active',
                'badge-secondary': vehicle.status === 'inactive',
                'badge-warning': vehicle.status === 'sold',
                'badge-danger': vehicle.status === 'scrapped'
              }">
                {{ $t(`vehicles.${vehicle.status}`) }}
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
                :to="`/vehicles/${vehicle.id}/edit`" 
                class="btn btn-warning"
                :title="$t('vehicles.editVehicle')"
              >
                <i class="fas fa-edit mr-2"></i>
                {{ $t('common.edit') }}
              </RouterLink>
              <button 
                v-if="canEdit"
                @click="showMileageDialog = true" 
                class="btn btn-info"
                :title="$t('vehicles.updateMileage')"
              >
                <i class="fas fa-tachometer-alt mr-2"></i>
                {{ $t('vehicles.updateMileage') }}
              </button>
              <button 
                v-if="canEdit"
                @click="showTransferDialog = true" 
                class="btn btn-primary"
                :title="$t('vehicles.transferOwnership')"
              >
                <i class="fas fa-exchange-alt mr-2"></i>
                {{ $t('vehicles.transferOwnership') }}
              </button>
              <button 
                v-if="canDelete"
                @click="confirmDelete" 
                class="btn btn-danger"
                :title="$t('vehicles.deleteVehicle')"
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
              <h3>{{ statistics.total_services || 0 }}</h3>
              <p>Total Services</p>
            </div>
            <div class="icon">
              <i class="fas fa-wrench"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-success">
            <div class="inner">
              <h3>{{ formatCurrency(statistics.total_cost || 0) }}</h3>
              <p>Total Cost</p>
            </div>
            <div class="icon">
              <i class="fas fa-dollar-sign"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3>{{ statistics.completed_services || 0 }}</h3>
              <p>Completed Services</p>
            </div>
            <div class="icon">
              <i class="fas fa-check-circle"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-danger">
            <div class="inner">
              <h3>{{ statistics.pending_services || 0 }}</h3>
              <p>Pending Services</p>
            </div>
            <div class="icon">
              <i class="fas fa-clock"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Vehicle Information Card -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-car mr-2"></i>
                {{ $t('vehicles.vehicleDetails') }}
              </h3>
            </div>
            <div class="card-body">
              <dl class="row mb-0">
                <dt class="col-sm-5">{{ $t('vehicles.vehicleNumber') }}:</dt>
                <dd class="col-sm-7">{{ vehicle.vehicle_number }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.registrationNumber') }}:</dt>
                <dd class="col-sm-7">{{ vehicle.registration_number }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.vinNumber') }}:</dt>
                <dd class="col-sm-7">{{ vehicle.vin_number || '-' }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.make') }}:</dt>
                <dd class="col-sm-7">{{ vehicle.make }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.model') }}:</dt>
                <dd class="col-sm-7">{{ vehicle.model }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.year') }}:</dt>
                <dd class="col-sm-7">{{ vehicle.year }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.color') }}:</dt>
                <dd class="col-sm-7">{{ vehicle.color || '-' }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.currentMileage') }}:</dt>
                <dd class="col-sm-7">{{ formatNumber(vehicle.current_mileage) }} km</dd>

                <dt class="col-sm-5">{{ $t('vehicles.owner') }}:</dt>
                <dd class="col-sm-7">
                  <RouterLink 
                    v-if="vehicle.customer"
                    :to="`/customers/${vehicle.customer_id}`" 
                    class="text-primary"
                  >
                    {{ vehicle.customer.first_name }} {{ vehicle.customer.last_name }}
                  </RouterLink>
                  <span v-else class="text-muted">-</span>
                </dd>
              </dl>
            </div>
          </div>
        </div>

        <!-- Technical & Insurance Info Card -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-cog mr-2"></i>
                Technical & Insurance
              </h3>
            </div>
            <div class="card-body">
              <dl class="row mb-0">
                <dt class="col-sm-5">{{ $t('vehicles.engineNumber') }}:</dt>
                <dd class="col-sm-7">{{ vehicle.engine_number || '-' }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.transmissionType') }}:</dt>
                <dd class="col-sm-7">{{ vehicle.transmission_type || '-' }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.fuelType') }}:</dt>
                <dd class="col-sm-7">{{ vehicle.fuel_type || '-' }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.insuranceProvider') }}:</dt>
                <dd class="col-sm-7">{{ vehicle.insurance_provider || '-' }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.insurancePolicyNumber') }}:</dt>
                <dd class="col-sm-7">{{ vehicle.insurance_policy_number || '-' }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.insuranceExpiryDate') }}:</dt>
                <dd class="col-sm-7">{{ formatDate(vehicle.insurance_expiry_date) }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.lastServiceDate') }}:</dt>
                <dd class="col-sm-7">{{ formatDate(vehicle.last_service_date) }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.nextServiceDueDate') }}:</dt>
                <dd class="col-sm-7">{{ formatDate(vehicle.next_service_due_date) }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.nextServiceDueMileage') }}:</dt>
                <dd class="col-sm-7">{{ vehicle.next_service_due_mileage ? formatNumber(vehicle.next_service_due_mileage) + ' km' : '-' }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <!-- Service History -->
      <div class="row mt-3">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                  <i class="fas fa-history mr-2"></i>
                  {{ $t('vehicles.serviceHistory') }}
                </h3>
              </div>
            </div>
            <div class="card-body">
              <!-- Empty State -->
              <div v-if="!serviceRecords || serviceRecords.length === 0" class="text-center py-5">
                <i class="fas fa-wrench fa-3x text-muted mb-3"></i>
                <p class="text-muted">{{ $t('vehicles.noServiceRecords') }}</p>
              </div>

              <!-- Service Records Table -->
              <div v-else class="table-responsive">
                <table class="table table-hover table-striped">
                  <thead>
                    <tr>
                      <th>{{ $t('serviceRecords.serviceNumber') }}</th>
                      <th>{{ $t('serviceRecords.serviceDate') }}</th>
                      <th>{{ $t('serviceRecords.serviceType') }}</th>
                      <th>{{ $t('serviceRecords.status') }}</th>
                      <th>{{ $t('serviceRecords.totalCost') }}</th>
                      <th>{{ $t('common.actions') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="record in serviceRecords" :key="record.id">
                      <td>
                        <RouterLink :to="`/service-records/${record.id}`" class="text-primary">
                          {{ record.service_number }}
                        </RouterLink>
                      </td>
                      <td>{{ formatDate(record.service_date) }}</td>
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
                        <RouterLink 
                          :to="`/service-records/${record.id}`" 
                          class="btn btn-sm btn-info"
                          :title="$t('serviceRecords.viewServiceRecord')"
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

    <!-- Update Mileage Dialog -->
    <ConfirmDialog
      v-if="showMileageDialog"
      :show="showMileageDialog"
      :title="$t('vehicles.updateMileage')"
      :message="`Current Mileage: ${formatNumber(vehicle?.current_mileage)} km`"
      @confirm="handleUpdateMileage"
      @cancel="showMileageDialog = false"
    >
      <template #default>
        <div class="form-group">
          <label>New Mileage (km)</label>
          <input 
            type="number" 
            class="form-control" 
            v-model.number="newMileage"
            :min="vehicle?.current_mileage"
          />
        </div>
      </template>
    </ConfirmDialog>

    <!-- Transfer Ownership Dialog -->
    <ConfirmDialog
      v-if="showTransferDialog"
      :show="showTransferDialog"
      :title="$t('vehicles.transferOwnership')"
      message="Select the new owner for this vehicle"
      @confirm="handleTransferOwnership"
      @cancel="showTransferDialog = false"
    >
      <template #default>
        <div class="form-group">
          <label>{{ $t('vehicles.newOwner') }}</label>
          <input 
            type="number" 
            class="form-control" 
            v-model.number="newOwnerId"
            placeholder="Enter Customer ID"
          />
        </div>
      </template>
    </ConfirmDialog>

    <!-- Confirm Delete Dialog -->
    <ConfirmDialog
      v-if="showDeleteDialog"
      :show="showDeleteDialog"
      :title="$t('vehicles.deleteVehicle')"
      :message="$t('vehicles.confirmDelete', { name: `${vehicle?.make} ${vehicle?.model} (${vehicle?.registration_number})` })"
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
import { useVehicleStore } from '@/stores/vehicle';
import { useServiceRecordStore } from '@/stores/serviceRecord';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Alert from '@/components/Alert.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Toast from '@/components/Toast.vue';

const router = useRouter();
const route = useRoute();
const vehicleStore = useVehicleStore();
const serviceRecordStore = useServiceRecordStore();
const authStore = useAuthStore();
const { t } = useI18n();

const toast = ref(null);
const vehicle = ref(null);
const statistics = ref(null);
const serviceRecords = ref([]);
const showDeleteDialog = ref(false);
const showMileageDialog = ref(false);
const showTransferDialog = ref(false);
const newMileage = ref(0);
const newOwnerId = ref(null);

// Permission checks
const canEdit = computed(() => {
  return authStore.hasPermission('vehicle.update') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

const canDelete = computed(() => {
  return authStore.hasPermission('vehicle.delete') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

const fetchVehicleData = async () => {
  try {
    const vehicleId = route.params.id;
    const [vehicleData, statsData, records] = await Promise.all([
      vehicleStore.fetchVehicleWithRelations(vehicleId),
      vehicleStore.fetchVehicleStatistics(vehicleId).catch(() => null),
      serviceRecordStore.fetchServiceRecordsByVehicle(vehicleId).catch(() => [])
    ]);
    vehicle.value = vehicleData;
    statistics.value = statsData;
    serviceRecords.value = records;
    newMileage.value = vehicleData.current_mileage;
  } catch (error) {
    console.error('Failed to fetch vehicle:', error);
    toast.value?.show(t('errors.generic'), 'error');
  }
};

const confirmDelete = () => {
  showDeleteDialog.value = true;
};

const handleDelete = async () => {
  try {
    await vehicleStore.deleteVehicle(vehicle.value.id);
    toast.value?.show(t('vehicles.deleteSuccess'), 'success');
    showDeleteDialog.value = false;
    router.push('/vehicles');
  } catch (error) {
    toast.value?.show(t('vehicles.deleteFailed'), 'error');
  }
};

const handleUpdateMileage = async () => {
  try {
    await vehicleStore.updateMileage(vehicle.value.id, newMileage.value);
    toast.value?.show(t('vehicles.mileageUpdateSuccess'), 'success');
    showMileageDialog.value = false;
    fetchVehicleData();
  } catch (error) {
    toast.value?.show(t('vehicles.mileageUpdateFailed'), 'error');
  }
};

const handleTransferOwnership = async () => {
  try {
    await vehicleStore.transferOwnership(vehicle.value.id, newOwnerId.value);
    toast.value?.show(t('vehicles.transferSuccess'), 'success');
    showTransferDialog.value = false;
    fetchVehicleData();
  } catch (error) {
    toast.value?.show(t('vehicles.transferFailed'), 'error');
  }
};

const goBack = () => {
  router.push('/vehicles');
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

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString();
};

onMounted(() => {
  fetchVehicleData();
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
