<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Sales Quotations</h1>
        <p class="mt-1 text-sm text-gray-500">Manage customer quotations and proposals</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Create Quotation
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Quotations</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-green-100 rounded-lg">
              <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Accepted</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.accepted }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-yellow-100 rounded-lg">
              <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Pending</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.sent }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-purple-100 rounded-lg">
              <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Value</p>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(stats.totalValue) }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search quotations..."
          type="search"
        />
        <BaseSelect
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="Filter by status"
        />
        <BaseInput
          v-model="fromDate"
          type="date"
          placeholder="From date"
          label="From Date"
        />
        <BaseInput
          v-model="toDate"
          type="date"
          placeholder="To date"
          label="To Date"
        />
      </div>
    </BaseCard>

    <!-- Quotations Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredQuotations"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewQuotation"
        @action:edit="editQuotation"
        @action:send="sendQuotation"
        @action:convert="convertToOrder"
        @action:delete="deleteQuotation"
      >
        <template #cell-quotation_code="{ value }">
          <span class="font-medium text-gray-900">{{ value }}</span>
        </template>

        <template #cell-customer="{ row }">
          <div>
            <div class="font-medium text-gray-900">{{ row.customer?.name || 'N/A' }}</div>
            <div class="text-sm text-gray-500">{{ row.customer?.email || '' }}</div>
          </div>
        </template>

        <template #cell-status="{ value }">
          <BaseBadge :variant="getStatusVariant(value)">
            {{ formatStatus(value) }}
          </BaseBadge>
        </template>

        <template #cell-quotation_date="{ value }">
          {{ formatDate(value) }}
        </template>

        <template #cell-valid_until="{ value }">
          {{ formatDate(value) }}
        </template>

        <template #cell-total_amount="{ value }">
          <span class="font-medium text-gray-900">{{ formatCurrency(value) }}</span>
        </template>
      </BaseTable>

      <div v-if="pagination.totalPages > 1" class="mt-4">
        <BasePagination
          :current-page="pagination.currentPage"
          :total-pages="pagination.totalPages"
          :total="pagination.total"
          :per-page="pagination.perPage"
          @page-change="handlePageChange"
        />
      </div>
    </BaseCard>

    <!-- Create/Edit Modal -->
    <BaseModal :show="modal.isOpen" :title="modalTitle" size="2xl" @close="modal.close">
      <form @submit.prevent="handleSubmit">
        <div class="space-y-4">
          <!-- Customer Selection -->
          <BaseSelect
            v-model="form.customer_id"
            label="Customer"
            :options="customerOptions"
            required
            placeholder="Select customer"
            :error="errors.customer_id"
          />

          <!-- Quotation Details -->
          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.quotation_code"
              label="Quotation Code"
              required
              placeholder="QT-001"
              :error="errors.quotation_code"
            />
            
            <BaseInput
              v-model="form.reference"
              label="Reference"
              placeholder="Customer PO/Reference"
              :error="errors.reference"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.quotation_date"
              label="Quotation Date"
              type="date"
              required
              :error="errors.quotation_date"
            />
            
            <BaseInput
              v-model="form.valid_until"
              label="Valid Until"
              type="date"
              required
              :error="errors.valid_until"
            />
          </div>

          <!-- Line Items -->
          <div class="border-t pt-4">
            <div class="flex items-center justify-between mb-3">
              <label class="block text-sm font-medium text-gray-700">Line Items</label>
              <BaseButton type="button" variant="secondary" size="sm" @click="addLineItem">
                Add Item
              </BaseButton>
            </div>

            <div v-for="(item, index) in form.items" :key="index" class="mb-3 p-3 border rounded-lg bg-gray-50">
              <div class="grid grid-cols-12 gap-2">
                <div class="col-span-5">
                  <BaseSelect
                    v-model="item.product_id"
                    :options="productOptions"
                    placeholder="Select product"
                    size="sm"
                  />
                </div>
                <div class="col-span-2">
                  <BaseInput
                    v-model.number="item.quantity"
                    type="number"
                    placeholder="Qty"
                    size="sm"
                    min="0.01"
                    step="0.01"
                  />
                </div>
                <div class="col-span-2">
                  <BaseInput
                    v-model.number="item.unit_price"
                    type="number"
                    placeholder="Price"
                    size="sm"
                    min="0"
                    step="0.01"
                  />
                </div>
                <div class="col-span-2">
                  <BaseInput
                    :model-value="(item.quantity * item.unit_price).toFixed(2)"
                    placeholder="Total"
                    size="sm"
                    disabled
                  />
                </div>
                <div class="col-span-1 flex items-center">
                  <button
                    type="button"
                    @click="removeLineItem(index)"
                    class="text-red-600 hover:text-red-800"
                  >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Totals -->
          <div class="border-t pt-4 space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-gray-600">Subtotal:</span>
              <span class="font-medium">{{ formatCurrency(calculateSubtotal()) }}</span>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <BaseInput
                v-model.number="form.tax_amount"
                label="Tax Amount"
                type="number"
                min="0"
                step="0.01"
              />
              <BaseInput
                v-model.number="form.discount_amount"
                label="Discount Amount"
                type="number"
                min="0"
                step="0.01"
              />
            </div>
            <div class="flex justify-between text-lg font-bold border-t pt-2">
              <span>Total:</span>
              <span>{{ formatCurrency(calculateTotal()) }}</span>
            </div>
          </div>

          <!-- Notes and Terms -->
          <BaseTextarea
            v-model="form.notes"
            label="Notes"
            placeholder="Additional notes..."
            rows="2"
          />

          <BaseTextarea
            v-model="form.terms_conditions"
            label="Terms & Conditions"
            placeholder="Payment terms and conditions..."
            rows="3"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="modal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            {{ isEditing ? 'Update' : 'Create' }} Quotation
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useSalesStore } from '../stores/salesStore';
import { useCrmStore } from '../../crm/stores/crmStore';
import { useProductStore } from '../../product/stores/productStore';
import { useModal } from '@/composables/useModal';
import { usePagination } from '@/composables/usePagination';
import { useNotifications } from '@/composables/useNotifications';
import BaseCard from '@/components/layout/BaseCard.vue';
import BaseTable from '@/components/data/BaseTable.vue';
import BasePagination from '@/components/data/BasePagination.vue';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseInput from '@/components/common/BaseInput.vue';
import BaseSelect from '@/components/common/BaseSelect.vue';
import BaseTextarea from '@/components/common/BaseTextarea.vue';
import BaseBadge from '@/components/common/BaseBadge.vue';
import BaseModal from '@/components/layout/BaseModal.vue';

const salesStore = useSalesStore();
const crmStore = useCrmStore();
const productStore = useProductStore();
const modal = useModal();
const { notify } = useNotifications();
const pagination = usePagination();

const loading = ref(false);
const saving = ref(false);
const search = ref('');
const statusFilter = ref('');
const fromDate = ref('');
const toDate = ref('');
const isEditing = ref(false);
const editingId = ref(null);

const form = ref({
  customer_id: '',
  quotation_code: '',
  reference: '',
  quotation_date: new Date().toISOString().split('T')[0],
  valid_until: '',
  tax_amount: 0,
  discount_amount: 0,
  notes: '',
  terms_conditions: '',
  items: []
});

const errors = ref({});

const stats = computed(() => ({
  total: salesStore.quotations?.length || 0,
  accepted: salesStore.quotations?.filter(q => q.status === 'accepted').length || 0,
  sent: salesStore.quotations?.filter(q => q.status === 'sent').length || 0,
  totalValue: salesStore.quotations?.reduce((sum, q) => sum + parseFloat(q.total_amount || 0), 0) || 0
}));

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'draft', label: 'Draft' },
  { value: 'sent', label: 'Sent' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'expired', label: 'Expired' },
  { value: 'converted', label: 'Converted' }
];

const customerOptions = computed(() => [
  { value: '', label: 'Select customer' },
  ...(crmStore.customers || []).map(c => ({ value: c.id, label: c.name }))
]);

const productOptions = computed(() => [
  { value: '', label: 'Select product' },
  ...(productStore.products || []).map(p => ({ 
    value: p.id, 
    label: `${p.name} - ${formatCurrency(p.selling_price || 0)}` 
  }))
]);

const columns = [
  { key: 'quotation_code', label: 'Quotation #', sortable: true },
  { key: 'customer', label: 'Customer', sortable: false },
  { key: 'quotation_date', label: 'Date', sortable: true },
  { key: 'valid_until', label: 'Valid Until', sortable: true },
  { key: 'total_amount', label: 'Amount', sortable: true },
  { key: 'status', label: 'Status', sortable: true }
];

const tableActions = computed(() => {
  return (row) => {
    const actions = [
      { key: 'view', label: 'View', icon: 'eye' },
      { key: 'edit', label: 'Edit', icon: 'pencil', show: row.status === 'draft' }
    ];
    
    if (row.status === 'draft') {
      actions.push({ key: 'send', label: 'Send to Customer', icon: 'paper-airplane' });
    }
    
    if (row.status === 'accepted') {
      actions.push({ key: 'convert', label: 'Convert to Order', icon: 'arrow-right' });
    }
    
    if (row.status === 'draft') {
      actions.push({ key: 'delete', label: 'Delete', icon: 'trash', variant: 'danger' });
    }
    
    return actions.filter(a => a.show !== false);
  };
});

const filteredQuotations = computed(() => {
  let result = salesStore.quotations || [];

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    result = result.filter(q =>
      q.quotation_code?.toLowerCase().includes(searchLower) ||
      q.customer?.name?.toLowerCase().includes(searchLower) ||
      q.reference?.toLowerCase().includes(searchLower)
    );
  }

  if (statusFilter.value) {
    result = result.filter(q => q.status === statusFilter.value);
  }

  if (fromDate.value) {
    result = result.filter(q => new Date(q.quotation_date) >= new Date(fromDate.value));
  }

  if (toDate.value) {
    result = result.filter(q => new Date(q.quotation_date) <= new Date(toDate.value));
  }

  return result;
});

const modalTitle = computed(() => isEditing.value ? 'Edit Quotation' : 'Create Quotation');

onMounted(async () => {
  loading.value = true;
  try {
    await Promise.all([
      salesStore.fetchQuotations(),
      crmStore.fetchCustomers(),
      productStore.fetchProducts()
    ]);
  } catch (error) {
    notify('Failed to load data', 'error');
  } finally {
    loading.value = false;
  }
});

function openCreateModal() {
  isEditing.value = false;
  editingId.value = null;
  resetForm();
  modal.open();
}

function editQuotation(quotation) {
  isEditing.value = true;
  editingId.value = quotation.id;
  form.value = {
    customer_id: quotation.customer_id,
    quotation_code: quotation.quotation_code,
    reference: quotation.reference || '',
    quotation_date: quotation.quotation_date,
    valid_until: quotation.valid_until,
    tax_amount: parseFloat(quotation.tax_amount || 0),
    discount_amount: parseFloat(quotation.discount_amount || 0),
    notes: quotation.notes || '',
    terms_conditions: quotation.terms_conditions || '',
    items: quotation.items?.map(item => ({
      product_id: item.product_id,
      quantity: parseFloat(item.quantity),
      unit_price: parseFloat(item.unit_price)
    })) || []
  };
  modal.open();
}

function viewQuotation(quotation) {
  notify('View functionality coming soon', 'info');
}

async function sendQuotation(quotation) {
  if (!confirm(`Send quotation ${quotation.quotation_code} to customer?`)) return;
  
  try {
    await salesStore.sendQuotation(quotation.id);
    notify('Quotation sent successfully', 'success');
  } catch (error) {
    notify('Failed to send quotation', 'error');
  }
}

async function convertToOrder(quotation) {
  if (!confirm(`Convert quotation ${quotation.quotation_code} to order?`)) return;
  
  try {
    await salesStore.convertQuotationToOrder(quotation.id);
    notify('Quotation converted to order successfully', 'success');
  } catch (error) {
    notify('Failed to convert quotation', 'error');
  }
}

async function deleteQuotation(quotation) {
  if (!confirm(`Delete quotation ${quotation.quotation_code}?`)) return;
  
  try {
    await salesStore.deleteQuotation(quotation.id);
    notify('Quotation deleted successfully', 'success');
  } catch (error) {
    notify('Failed to delete quotation', 'error');
  }
}

function addLineItem() {
  form.value.items.push({
    product_id: '',
    quantity: 1,
    unit_price: 0
  });
}

function removeLineItem(index) {
  form.value.items.splice(index, 1);
}

function calculateSubtotal() {
  return form.value.items.reduce((sum, item) => {
    return sum + (item.quantity * item.unit_price);
  }, 0);
}

function calculateTotal() {
  const subtotal = calculateSubtotal();
  const tax = parseFloat(form.value.tax_amount || 0);
  const discount = parseFloat(form.value.discount_amount || 0);
  return subtotal + tax - discount;
}

async function handleSubmit() {
  errors.value = {};
  
  if (!form.value.customer_id) {
    errors.value.customer_id = 'Customer is required';
    return;
  }
  
  if (!form.value.items.length) {
    notify('Please add at least one line item', 'error');
    return;
  }

  saving.value = true;
  try {
    const data = {
      ...form.value,
      subtotal: calculateSubtotal(),
      total_amount: calculateTotal()
    };

    if (isEditing.value) {
      await salesStore.updateQuotation(editingId.value, data);
      notify('Quotation updated successfully', 'success');
    } else {
      await salesStore.createQuotation(data);
      notify('Quotation created successfully', 'success');
    }
    
    modal.close();
    resetForm();
  } catch (error) {
    notify(error.message || 'Failed to save quotation', 'error');
  } finally {
    saving.value = false;
  }
}

function resetForm() {
  form.value = {
    customer_id: '',
    quotation_code: '',
    reference: '',
    quotation_date: new Date().toISOString().split('T')[0],
    valid_until: '',
    tax_amount: 0,
    discount_amount: 0,
    notes: '',
    terms_conditions: '',
    items: []
  };
  errors.value = {};
}

function handleSort(column) {
  // Implement sorting logic
}

function handlePageChange(page) {
  pagination.currentPage = page;
}

function getStatusVariant(status) {
  const variants = {
    draft: 'secondary',
    sent: 'warning',
    accepted: 'success',
    rejected: 'danger',
    expired: 'secondary',
    converted: 'primary'
  };
  return variants[status] || 'secondary';
}

function formatStatus(status) {
  const labels = {
    draft: 'Draft',
    sent: 'Sent',
    accepted: 'Accepted',
    rejected: 'Rejected',
    expired: 'Expired',
    converted: 'Converted'
  };
  return labels[status] || status;
}

function formatDate(date) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString();
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0);
}
</script>
