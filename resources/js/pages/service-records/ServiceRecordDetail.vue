<template>
  <AdminLayout :page-title="$t('serviceRecords.serviceRecordDetails')">
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

    <!-- Service Record Details -->
    <div v-else-if="serviceRecord">
      <!-- Header with Actions -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="mb-0">
              {{ serviceRecord.service_number }}
              <span class="badge ml-2" :class="{
                'badge-warning': serviceRecord.status === 'pending',
                'badge-info': serviceRecord.status === 'in_progress',
                'badge-success': serviceRecord.status === 'completed',
                'badge-danger': serviceRecord.status === 'cancelled'
              }">
                {{ $t(`serviceRecords.${serviceRecord.status}`) }}
              </span>
            </h3>
            <div class="btn-group mt-2 mt-md-0" role="group">
              <button 
                @click="goBack" 
                class="btn btn-secondary"
                :title="$t('common.back')"
              >
                <i class="fas fa-arrow-left mr-2"></i>
                {{ $t('common.back') }}
              </button>
              <RouterLink 
                v-if="canEdit && serviceRecord.status !== 'completed' && serviceRecord.status !== 'cancelled'"
                :to="`/service-records/${serviceRecord.id}/edit`" 
                class="btn btn-warning"
                :title="$t('serviceRecords.editServiceRecord')"
              >
                <i class="fas fa-edit mr-2"></i>
                {{ $t('common.edit') }}
              </RouterLink>
              <button 
                v-if="canEdit && serviceRecord.status === 'in_progress'"
                @click="confirmComplete" 
                class="btn btn-success"
                :title="$t('serviceRecords.complete')"
              >
                <i class="fas fa-check mr-2"></i>
                {{ $t('serviceRecords.complete') }}
              </button>
              <button 
                v-if="canEdit && (serviceRecord.status === 'pending' || serviceRecord.status === 'in_progress')"
                @click="confirmCancel" 
                class="btn btn-secondary"
                :title="$t('serviceRecords.cancel')"
              >
                <i class="fas fa-ban mr-2"></i>
                {{ $t('serviceRecords.cancel') }}
              </button>
              <button 
                v-if="canDelete"
                @click="confirmDelete" 
                class="btn btn-danger"
                :title="$t('serviceRecords.deleteServiceRecord')"
              >
                <i class="fas fa-trash mr-2"></i>
                {{ $t('common.delete') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Service Information Card -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-wrench mr-2"></i>
                {{ $t('serviceRecords.serviceRecordDetails') }}
              </h3>
            </div>
            <div class="card-body">
              <dl class="row mb-0">
                <dt class="col-sm-5">{{ $t('serviceRecords.serviceNumber') }}:</dt>
                <dd class="col-sm-7">{{ serviceRecord.service_number }}</dd>

                <dt class="col-sm-5">{{ $t('serviceRecords.serviceDate') }}:</dt>
                <dd class="col-sm-7">{{ formatDate(serviceRecord.service_date) }}</dd>

                <dt class="col-sm-5">{{ $t('serviceRecords.serviceType') }}:</dt>
                <dd class="col-sm-7">{{ serviceRecord.service_type }}</dd>

                <dt class="col-sm-5">{{ $t('serviceRecords.status') }}:</dt>
                <dd class="col-sm-7">
                  <span class="badge" :class="{
                    'badge-warning': serviceRecord.status === 'pending',
                    'badge-info': serviceRecord.status === 'in_progress',
                    'badge-success': serviceRecord.status === 'completed',
                    'badge-danger': serviceRecord.status === 'cancelled'
                  }">
                    {{ $t(`serviceRecords.${serviceRecord.status}`) }}
                  </span>
                </dd>

                <dt class="col-sm-5" v-if="serviceRecord.branch">{{ $t('serviceRecords.branch') }}:</dt>
                <dd class="col-sm-7" v-if="serviceRecord.branch">{{ serviceRecord.branch.name || '-' }}</dd>

                <dt class="col-sm-5" v-if="serviceRecord.technician">{{ $t('serviceRecords.technician') }}:</dt>
                <dd class="col-sm-7" v-if="serviceRecord.technician">{{ serviceRecord.technician || '-' }}</dd>

                <dt class="col-sm-5">{{ $t('serviceRecords.laborCost') }}:</dt>
                <dd class="col-sm-7">{{ formatCurrency(serviceRecord.labor_cost) }}</dd>

                <dt class="col-sm-5">{{ $t('serviceRecords.partsCost') }}:</dt>
                <dd class="col-sm-7">{{ formatCurrency(serviceRecord.parts_cost) }}</dd>

                <dt class="col-sm-5"><strong>{{ $t('serviceRecords.totalCost') }}:</strong></dt>
                <dd class="col-sm-7"><strong>{{ formatCurrency(serviceRecord.total_cost) }}</strong></dd>

                <dt class="col-sm-5" v-if="serviceRecord.next_service_date">{{ $t('serviceRecords.nextServiceDate') }}:</dt>
                <dd class="col-sm-7" v-if="serviceRecord.next_service_date">{{ formatDate(serviceRecord.next_service_date) }}</dd>

                <dt class="col-sm-5" v-if="serviceRecord.next_service_mileage">{{ $t('serviceRecords.nextServiceMileage') }}:</dt>
                <dd class="col-sm-7" v-if="serviceRecord.next_service_mileage">{{ formatNumber(serviceRecord.next_service_mileage) }} km</dd>
              </dl>
            </div>
          </div>

          <!-- Description Card -->
          <div class="card" v-if="serviceRecord.description">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-file-alt mr-2"></i>
                {{ $t('serviceRecords.description') }}
              </h3>
            </div>
            <div class="card-body">
              <p class="mb-0">{{ serviceRecord.description }}</p>
            </div>
          </div>

          <!-- Notes Card -->
          <div class="card" v-if="serviceRecord.notes">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-sticky-note mr-2"></i>
                {{ $t('serviceRecords.notes') }}
              </h3>
            </div>
            <div class="card-body">
              <p class="mb-0">{{ serviceRecord.notes }}</p>
            </div>
          </div>
        </div>

        <!-- Vehicle & Customer Information -->
        <div class="col-md-6">
          <!-- Vehicle Card -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-car mr-2"></i>
                {{ $t('serviceRecords.vehicle') }}
              </h3>
            </div>
            <div class="card-body">
              <dl class="row mb-0" v-if="serviceRecord.vehicle">
                <dt class="col-sm-5">{{ $t('vehicles.vehicleNumber') }}:</dt>
                <dd class="col-sm-7">
                  <RouterLink :to="`/vehicles/${serviceRecord.vehicle_id}`" class="text-primary">
                    {{ serviceRecord.vehicle.vehicle_number }}
                  </RouterLink>
                </dd>

                <dt class="col-sm-5">{{ $t('vehicles.registrationNumber') }}:</dt>
                <dd class="col-sm-7">{{ serviceRecord.vehicle.registration_number }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.make') }}:</dt>
                <dd class="col-sm-7">{{ serviceRecord.vehicle.make }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.model') }}:</dt>
                <dd class="col-sm-7">{{ serviceRecord.vehicle.model }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.year') }}:</dt>
                <dd class="col-sm-7">{{ serviceRecord.vehicle.year }}</dd>

                <dt class="col-sm-5">{{ $t('vehicles.mileage') }}:</dt>
                <dd class="col-sm-7">{{ formatNumber(serviceRecord.vehicle.current_mileage) }} km</dd>
              </dl>
              <p v-else class="text-muted mb-0">No vehicle information available</p>
            </div>
          </div>

          <!-- Customer Card -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-user mr-2"></i>
                {{ $t('serviceRecords.customer') }}
              </h3>
            </div>
            <div class="card-body">
              <dl class="row mb-0" v-if="serviceRecord.customer">
                <dt class="col-sm-5">{{ $t('customers.customerNumber') }}:</dt>
                <dd class="col-sm-7">
                  <RouterLink :to="`/customers/${serviceRecord.customer_id}`" class="text-primary">
                    {{ serviceRecord.customer.customer_number }}
                  </RouterLink>
                </dd>

                <dt class="col-sm-5">{{ $t('customers.firstName') }}:</dt>
                <dd class="col-sm-7">{{ serviceRecord.customer.first_name }}</dd>

                <dt class="col-sm-5">{{ $t('customers.lastName') }}:</dt>
                <dd class="col-sm-7">{{ serviceRecord.customer.last_name }}</dd>

                <dt class="col-sm-5">{{ $t('customers.email') }}:</dt>
                <dd class="col-sm-7">{{ serviceRecord.customer.email || '-' }}</dd>

                <dt class="col-sm-5">{{ $t('customers.phone') }}:</dt>
                <dd class="col-sm-7">{{ serviceRecord.customer.phone || serviceRecord.customer.mobile || '-' }}</dd>
              </dl>
              <p v-else class="text-muted mb-0">No customer information available</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Parts Used -->
      <div class="row mt-3" v-if="serviceRecord.parts_used && serviceRecord.parts_used.length > 0">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">
                <i class="fas fa-cogs mr-2"></i>
                {{ $t('serviceRecords.partsUsed') }}
              </h3>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover table-striped">
                  <thead>
                    <tr>
                      <th>Part Name</th>
                      <th>Part Number</th>
                      <th>Quantity</th>
                      <th>Unit Price</th>
                      <th>Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(part, index) in serviceRecord.parts_used" :key="index">
                      <td>{{ part.name }}</td>
                      <td>{{ part.part_number || '-' }}</td>
                      <td>{{ part.quantity }}</td>
                      <td>{{ formatCurrency(part.unit_price) }}</td>
                      <td>{{ formatCurrency(part.quantity * part.unit_price) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm Complete Dialog -->
    <ConfirmDialog
      v-if="showCompleteDialog"
      :show="showCompleteDialog"
      :title="$t('serviceRecords.complete')"
      :message="$t('serviceRecords.confirmComplete')"
      @confirm="handleComplete"
      @cancel="showCompleteDialog = false"
    />

    <!-- Confirm Cancel Dialog -->
    <ConfirmDialog
      v-if="showCancelDialog"
      :show="showCancelDialog"
      :title="$t('serviceRecords.cancel')"
      :message="$t('serviceRecords.confirmCancel')"
      @confirm="handleCancel"
      @cancel="showCancelDialog = false"
    />

    <!-- Confirm Delete Dialog -->
    <ConfirmDialog
      v-if="showDeleteDialog"
      :show="showDeleteDialog"
      :title="$t('serviceRecords.deleteServiceRecord')"
      :message="$t('serviceRecords.confirmDelete')"
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
import { useServiceRecordStore } from '@/stores/serviceRecord';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Alert from '@/components/Alert.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Toast from '@/components/Toast.vue';

const router = useRouter();
const route = useRoute();
const serviceRecordStore = useServiceRecordStore();
const authStore = useAuthStore();
const { t } = useI18n();

const toast = ref(null);
const serviceRecord = ref(null);
const showDeleteDialog = ref(false);
const showCompleteDialog = ref(false);
const showCancelDialog = ref(false);

// Permission checks
const canEdit = computed(() => {
  return authStore.hasPermission('service-record.update') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

const canDelete = computed(() => {
  return authStore.hasPermission('service-record.delete') || authStore.hasRole('super-admin') || authStore.hasRole('admin');
});

const fetchServiceRecordData = async () => {
  try {
    const recordId = route.params.id;
    const data = await serviceRecordStore.fetchServiceRecordWithRelations(recordId);
    serviceRecord.value = data;
  } catch (error) {
    console.error('Failed to fetch service record:', error);
    toast.value?.show(t('errors.generic'), 'error');
  }
};

const confirmComplete = () => {
  showCompleteDialog.value = true;
};

const handleComplete = async () => {
  try {
    await serviceRecordStore.completeServiceRecord(serviceRecord.value.id);
    toast.value?.show(t('serviceRecords.completeSuccess'), 'success');
    showCompleteDialog.value = false;
    fetchServiceRecordData();
  } catch (error) {
    toast.value?.show(t('serviceRecords.completeFailed'), 'error');
  }
};

const confirmCancel = () => {
  showCancelDialog.value = true;
};

const handleCancel = async () => {
  try {
    await serviceRecordStore.cancelServiceRecord(serviceRecord.value.id);
    toast.value?.show(t('serviceRecords.cancelSuccess'), 'success');
    showCancelDialog.value = false;
    fetchServiceRecordData();
  } catch (error) {
    toast.value?.show(t('serviceRecords.cancelFailed'), 'error');
  }
};

const confirmDelete = () => {
  showDeleteDialog.value = true;
};

const handleDelete = async () => {
  try {
    await serviceRecordStore.deleteServiceRecord(serviceRecord.value.id);
    toast.value?.show(t('serviceRecords.deleteSuccess'), 'success');
    showDeleteDialog.value = false;
    router.push('/service-records');
  } catch (error) {
    toast.value?.show(t('serviceRecords.deleteFailed'), 'error');
  }
};

const goBack = () => {
  router.push('/service-records');
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
  fetchServiceRecordData();
});
</script>

<style scoped>
dl.row dt {
  font-weight: 600;
}

dl.row dd {
  margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
  .btn-group {
    flex-wrap: wrap;
  }
  
  .btn-group .btn {
    margin-bottom: 0.25rem;
  }
}
</style>
