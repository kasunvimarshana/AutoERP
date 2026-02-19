<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Sales Orders</h1>
        <p class="mt-1 text-sm text-gray-500">Manage customer orders and fulfillment</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Create Order
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Orders</p>
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
            <p class="text-sm font-medium text-gray-500">Confirmed</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.confirmed }}</p>
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
            <p class="text-sm font-medium text-gray-500">In Progress</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.processing }}</p>
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
          placeholder="Search orders..."
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

    <!-- Orders Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredOrders"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewOrder"
        @action:edit="editOrder"
        @action:confirm="confirmOrder"
        @action:ship="shipOrder"
        @action:deliver="deliverOrder"
        @action:cancel="cancelOrder"
        @action:delete="deleteOrder"
      >
        <template #cell-order_code="{ value }">
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

        <template #cell-order_date="{ value }">
          {{ formatDate(value) }}
        </template>

        <template #cell-delivery_date="{ value }">
          {{ formatDate(value) }}
        </template>

        <template #cell-total_amount="{ value }">
          <span class="font-medium text-gray-900">{{ formatCurrency(value) }}</span>
        </template>

        <template #cell-paid_amount="{ value }">
          <span class="font-medium text-green-600">{{ formatCurrency(value) }}</span>
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

          <!-- Order Details -->
          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.order_code"
              label="Order Code"
              required
              placeholder="SO-001"
              :error="errors.order_code"
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
              v-model="form.order_date"
              label="Order Date"
              type="date"
              required
              :error="errors.order_date"
            />
            
            <BaseInput
              v-model="form.delivery_date"
              label="Expected Delivery"
              type="date"
              :error="errors.delivery_date"
            />
          </div>

          <!-- Shipping Address -->
          <BaseTextarea
            v-model="form.shipping_address"
            label="Shipping Address"
            placeholder="Delivery address..."
            rows="2"
          />

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
            <div class="grid grid-cols-3 gap-4">
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
              <BaseInput
                v-model.number="form.shipping_cost"
                label="Shipping Cost"
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

          <!-- Notes -->
          <BaseTextarea
            v-model="form.notes"
            label="Notes"
            placeholder="Additional notes..."
            rows="2"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="modal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            {{ isEditing ? 'Update' : 'Create' }} Order
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
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
const sortColumn = ref('');
const sortDirection = ref('asc');

const form = ref({
  customer_id: '',
  order_code: '',
  reference: '',
  order_date: new Date().toISOString().split('T')[0],
  delivery_date: '',
  shipping_address: '',
  tax_amount: 0,
  discount_amount: 0,
  shipping_cost: 0,
  notes: '',
  items: []
});

const errors = ref({});

const stats = computed(() => ({
  total: salesStore.orders?.length || 0,
  confirmed: salesStore.orders?.filter(o => o.status === 'confirmed').length || 0,
  processing: salesStore.orders?.filter(o => ['processing', 'pending'].includes(o.status)).length || 0,
  totalValue: salesStore.orders?.reduce((sum, o) => sum + parseFloat(o.total_amount || 0), 0) || 0
}));

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'draft', label: 'Draft' },
  { value: 'pending', label: 'Pending' },
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'processing', label: 'Processing' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' }
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
  { key: 'order_code', label: 'Order #', sortable: true },
  { key: 'customer', label: 'Customer', sortable: false },
  { key: 'order_date', label: 'Order Date', sortable: true },
  { key: 'delivery_date', label: 'Delivery Date', sortable: true },
  { key: 'total_amount', label: 'Total', sortable: true },
  { key: 'paid_amount', label: 'Paid', sortable: true },
  { key: 'status', label: 'Status', sortable: true }
];

const tableActions = computed(() => {
  return (row) => {
    const actions = [
      { key: 'view', label: 'View', icon: 'eye' },
      { key: 'edit', label: 'Edit', icon: 'pencil', show: ['draft', 'pending'].includes(row.status) }
    ];
    
    if (row.status === 'pending') {
      actions.push({ key: 'confirm', label: 'Confirm Order', icon: 'check' });
    }
    
    if (row.status === 'confirmed') {
      actions.push({ key: 'ship', label: 'Mark as Shipped', icon: 'truck' });
    }
    
    if (row.status === 'processing') {
      actions.push({ key: 'deliver', label: 'Mark as Delivered', icon: 'check-circle' });
    }
    
    if (['draft', 'pending', 'confirmed'].includes(row.status)) {
      actions.push({ key: 'cancel', label: 'Cancel Order', icon: 'x-circle', variant: 'warning' });
    }
    
    if (row.status === 'draft') {
      actions.push({ key: 'delete', label: 'Delete', icon: 'trash', variant: 'danger' });
    }
    
    return actions.filter(a => a.show !== false);
  };
});

const filteredOrders = computed(() => {
  let result = salesStore.orders || [];

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    result = result.filter(o =>
      o.order_code?.toLowerCase().includes(searchLower) ||
      o.customer?.name?.toLowerCase().includes(searchLower) ||
      o.reference?.toLowerCase().includes(searchLower)
    );
  }

  if (statusFilter.value) {
    result = result.filter(o => o.status === statusFilter.value);
  }

  if (fromDate.value) {
    result = result.filter(o => new Date(o.order_date) >= new Date(fromDate.value));
  }

  if (toDate.value) {
    result = result.filter(o => new Date(o.order_date) <= new Date(toDate.value));
  }

  if (sortColumn.value) {
    result.sort((a, b) => {
      let aVal = a[sortColumn.value];
      let bVal = b[sortColumn.value];
      
      if (sortColumn.value === 'order_date' || sortColumn.value === 'delivery_date') {
        aVal = new Date(aVal || 0);
        bVal = new Date(bVal || 0);
      } else if (sortColumn.value === 'total_amount' || sortColumn.value === 'paid_amount') {
        aVal = parseFloat(aVal || 0);
        bVal = parseFloat(bVal || 0);
      }
      
      if (aVal < bVal) return sortDirection.value === 'asc' ? -1 : 1;
      if (aVal > bVal) return sortDirection.value === 'asc' ? 1 : -1;
      return 0;
    });
  }

  return result;
});

const modalTitle = computed(() => isEditing.value ? 'Edit Order' : 'Create Order');

onMounted(async () => {
  loading.value = true;
  try {
    await Promise.all([
      salesStore.fetchOrders(),
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

function editOrder(order) {
  isEditing.value = true;
  editingId.value = order.id;
  form.value = {
    customer_id: order.customer_id,
    order_code: order.order_code,
    reference: order.reference || '',
    order_date: order.order_date,
    delivery_date: order.delivery_date || '',
    shipping_address: order.shipping_address || '',
    tax_amount: parseFloat(order.tax_amount || 0),
    discount_amount: parseFloat(order.discount_amount || 0),
    shipping_cost: parseFloat(order.shipping_cost || 0),
    notes: order.notes || '',
    items: order.items?.map(item => ({
      product_id: item.product_id,
      quantity: parseFloat(item.quantity),
      unit_price: parseFloat(item.unit_price)
    })) || []
  };
  modal.open();
}

function viewOrder(order) {
  notify('View functionality coming soon', 'info');
}

async function confirmOrder(order) {
  if (!confirm(`Confirm order ${order.order_code}?`)) return;
  
  try {
    await salesStore.confirmOrder(order.id);
    notify('Order confirmed successfully', 'success');
  } catch (error) {
    notify('Failed to confirm order', 'error');
  }
}

async function shipOrder(order) {
  if (!confirm(`Mark order ${order.order_code} as shipped?`)) return;
  
  try {
    await salesStore.shipOrder(order.id);
    notify('Order marked as shipped', 'success');
  } catch (error) {
    notify('Failed to ship order', 'error');
  }
}

async function deliverOrder(order) {
  if (!confirm(`Mark order ${order.order_code} as delivered?`)) return;
  
  try {
    await salesStore.deliverOrder(order.id);
    notify('Order marked as delivered', 'success');
  } catch (error) {
    notify('Failed to deliver order', 'error');
  }
}

async function cancelOrder(order) {
  if (!confirm(`Cancel order ${order.order_code}?`)) return;
  
  try {
    await salesStore.cancelOrder(order.id);
    notify('Order cancelled successfully', 'success');
  } catch (error) {
    notify('Failed to cancel order', 'error');
  }
}

async function deleteOrder(order) {
  if (!confirm(`Delete order ${order.order_code}?`)) return;
  
  try {
    await salesStore.deleteOrder(order.id);
    notify('Order deleted successfully', 'success');
  } catch (error) {
    notify('Failed to delete order', 'error');
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
  const shipping = parseFloat(form.value.shipping_cost || 0);
  return subtotal + tax - discount + shipping;
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
      await salesStore.updateOrder(editingId.value, data);
      notify('Order updated successfully', 'success');
    } else {
      await salesStore.createOrder(data);
      notify('Order created successfully', 'success');
    }
    
    modal.close();
    resetForm();
  } catch (error) {
    notify(error.message || 'Failed to save order', 'error');
  } finally {
    saving.value = false;
  }
}

function resetForm() {
  form.value = {
    customer_id: '',
    order_code: '',
    reference: '',
    order_date: new Date().toISOString().split('T')[0],
    delivery_date: '',
    shipping_address: '',
    tax_amount: 0,
    discount_amount: 0,
    shipping_cost: 0,
    notes: '',
    items: []
  };
  errors.value = {};
}

function handleSort(column) {
  if (sortColumn.value === column) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortColumn.value = column;
    sortDirection.value = 'asc';
  }
}

function handlePageChange(page) {
  pagination.currentPage = page;
}

function getStatusVariant(status) {
  const variants = {
    draft: 'secondary',
    pending: 'warning',
    confirmed: 'primary',
    processing: 'info',
    completed: 'success',
    cancelled: 'danger'
  };
  return variants[status] || 'secondary';
}

function formatStatus(status) {
  const labels = {
    draft: 'Draft',
    pending: 'Pending',
    confirmed: 'Confirmed',
    processing: 'Processing',
    completed: 'Completed',
    cancelled: 'Cancelled'
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
