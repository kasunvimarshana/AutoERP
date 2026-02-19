<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Invoices</h1>
        <p class="mt-1 text-sm text-gray-500">Manage customer invoices and payments</p>
      </div>
      <BaseButton variant="primary" @click="openCreateModal">
        Create Invoice
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
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
            <p class="text-sm font-medium text-gray-500">Total Invoices</p>
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
            <p class="text-sm font-medium text-gray-500">Paid</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.paid }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-red-100 rounded-lg">
              <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Unpaid</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.unpaid }}</p>
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

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-orange-100 rounded-lg">
              <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Outstanding</p>
            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(stats.outstanding) }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search invoices..."
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

    <!-- Invoices Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredInvoices"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewInvoice"
        @action:edit="editInvoice"
        @action:send="sendInvoice"
        @action:payment="recordPayment"
        @action:paid="markAsPaid"
        @action:delete="deleteInvoice"
      >
        <template #cell-invoice_code="{ value }">
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

        <template #cell-invoice_date="{ value }">
          {{ formatDate(value) }}
        </template>

        <template #cell-due_date="{ value }">
          {{ formatDate(value) }}
        </template>

        <template #cell-total_amount="{ value }">
          <span class="font-medium text-gray-900">{{ formatCurrency(value) }}</span>
        </template>

        <template #cell-paid_amount="{ value }">
          <span class="font-medium text-green-600">{{ formatCurrency(value) }}</span>
        </template>

        <template #cell-balance="{ row }">
          <span class="font-medium text-red-600">
            {{ formatCurrency((row.total_amount || 0) - (row.paid_amount || 0)) }}
          </span>
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

          <!-- Invoice Details -->
          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.invoice_code"
              label="Invoice Code"
              required
              placeholder="INV-001"
              :error="errors.invoice_code"
            />
            
            <BaseInput
              v-model="form.reference"
              label="Reference"
              placeholder="Order/PO Reference"
              :error="errors.reference"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <BaseInput
              v-model="form.invoice_date"
              label="Invoice Date"
              type="date"
              required
              :error="errors.invoice_date"
            />
            
            <BaseInput
              v-model="form.due_date"
              label="Due Date"
              type="date"
              required
              :error="errors.due_date"
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

          <!-- Payment Terms and Notes -->
          <BaseTextarea
            v-model="form.payment_terms"
            label="Payment Terms"
            placeholder="Payment terms..."
            rows="2"
          />

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
            {{ isEditing ? 'Update' : 'Create' }} Invoice
          </BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Payment Modal -->
    <BaseModal :show="paymentModal.isOpen" title="Record Payment" @close="paymentModal.close">
      <form @submit.prevent="handlePaymentSubmit">
        <div class="space-y-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <div class="flex justify-between mb-2">
              <span class="text-sm text-gray-600">Invoice Total:</span>
              <span class="font-medium">{{ formatCurrency(selectedInvoice?.total_amount || 0) }}</span>
            </div>
            <div class="flex justify-between mb-2">
              <span class="text-sm text-gray-600">Paid Amount:</span>
              <span class="font-medium text-green-600">{{ formatCurrency(selectedInvoice?.paid_amount || 0) }}</span>
            </div>
            <div class="flex justify-between border-t pt-2">
              <span class="font-medium text-gray-900">Balance Due:</span>
              <span class="font-bold text-red-600">
                {{ formatCurrency((selectedInvoice?.total_amount || 0) - (selectedInvoice?.paid_amount || 0)) }}
              </span>
            </div>
          </div>

          <BaseInput
            v-model.number="paymentForm.amount"
            label="Payment Amount"
            type="number"
            required
            min="0.01"
            step="0.01"
            placeholder="0.00"
            :error="paymentErrors.amount"
          />

          <BaseInput
            v-model="paymentForm.payment_date"
            label="Payment Date"
            type="date"
            required
            :error="paymentErrors.payment_date"
          />

          <BaseSelect
            v-model="paymentForm.payment_method"
            label="Payment Method"
            :options="paymentMethodOptions"
            required
            :error="paymentErrors.payment_method"
          />

          <BaseInput
            v-model="paymentForm.transaction_reference"
            label="Transaction Reference"
            placeholder="Transaction ID/Reference"
          />

          <BaseTextarea
            v-model="paymentForm.notes"
            label="Notes"
            placeholder="Payment notes..."
            rows="2"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="paymentModal.close">
            Cancel
          </BaseButton>
          <BaseButton type="submit" variant="primary" :loading="saving">
            Record Payment
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
const paymentModal = useModal();
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
const selectedInvoice = ref(null);
const sortColumn = ref('');
const sortDirection = ref('asc');

const form = ref({
  customer_id: '',
  invoice_code: '',
  reference: '',
  invoice_date: new Date().toISOString().split('T')[0],
  due_date: '',
  tax_amount: 0,
  discount_amount: 0,
  shipping_cost: 0,
  payment_terms: '',
  notes: '',
  items: []
});

const paymentForm = ref({
  amount: 0,
  payment_date: new Date().toISOString().split('T')[0],
  payment_method: '',
  transaction_reference: '',
  notes: ''
});

const errors = ref({});
const paymentErrors = ref({});

const stats = computed(() => ({
  total: salesStore.invoices?.length || 0,
  paid: salesStore.invoices?.filter(i => i.status === 'paid').length || 0,
  unpaid: salesStore.invoices?.filter(i => ['unpaid', 'overdue'].includes(i.status)).length || 0,
  totalValue: salesStore.invoices?.reduce((sum, i) => sum + parseFloat(i.total_amount || 0), 0) || 0,
  outstanding: salesStore.invoices?.reduce((sum, i) => {
    const balance = parseFloat(i.total_amount || 0) - parseFloat(i.paid_amount || 0);
    return sum + (balance > 0 ? balance : 0);
  }, 0) || 0
}));

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'draft', label: 'Draft' },
  { value: 'sent', label: 'Sent' },
  { value: 'unpaid', label: 'Unpaid' },
  { value: 'partially_paid', label: 'Partially Paid' },
  { value: 'paid', label: 'Paid' },
  { value: 'overdue', label: 'Overdue' },
  { value: 'cancelled', label: 'Cancelled' }
];

const paymentMethodOptions = [
  { value: '', label: 'Select payment method' },
  { value: 'cash', label: 'Cash' },
  { value: 'bank_transfer', label: 'Bank Transfer' },
  { value: 'credit_card', label: 'Credit Card' },
  { value: 'debit_card', label: 'Debit Card' },
  { value: 'check', label: 'Check' },
  { value: 'other', label: 'Other' }
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
  { key: 'invoice_code', label: 'Invoice #', sortable: true },
  { key: 'customer', label: 'Customer', sortable: false },
  { key: 'invoice_date', label: 'Invoice Date', sortable: true },
  { key: 'due_date', label: 'Due Date', sortable: true },
  { key: 'total_amount', label: 'Total', sortable: true },
  { key: 'paid_amount', label: 'Paid', sortable: true },
  { key: 'balance', label: 'Balance', sortable: false },
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
    
    if (['sent', 'unpaid', 'partially_paid', 'overdue'].includes(row.status)) {
      actions.push({ key: 'payment', label: 'Record Payment', icon: 'currency-dollar' });
    }
    
    if (['unpaid', 'partially_paid', 'overdue'].includes(row.status)) {
      const balance = parseFloat(row.total_amount || 0) - parseFloat(row.paid_amount || 0);
      if (balance <= 0.01) {
        actions.push({ key: 'paid', label: 'Mark as Paid', icon: 'check' });
      }
    }
    
    if (row.status === 'draft') {
      actions.push({ key: 'delete', label: 'Delete', icon: 'trash', variant: 'danger' });
    }
    
    return actions.filter(a => a.show !== false);
  };
});

const filteredInvoices = computed(() => {
  let result = salesStore.invoices || [];

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    result = result.filter(i =>
      i.invoice_code?.toLowerCase().includes(searchLower) ||
      i.customer?.name?.toLowerCase().includes(searchLower) ||
      i.reference?.toLowerCase().includes(searchLower)
    );
  }

  if (statusFilter.value) {
    result = result.filter(i => i.status === statusFilter.value);
  }

  if (fromDate.value) {
    result = result.filter(i => new Date(i.invoice_date) >= new Date(fromDate.value));
  }

  if (toDate.value) {
    result = result.filter(i => new Date(i.invoice_date) <= new Date(toDate.value));
  }

  if (sortColumn.value) {
    result.sort((a, b) => {
      let aVal = a[sortColumn.value];
      let bVal = b[sortColumn.value];
      
      if (sortColumn.value === 'invoice_date' || sortColumn.value === 'due_date') {
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

const modalTitle = computed(() => isEditing.value ? 'Edit Invoice' : 'Create Invoice');

onMounted(async () => {
  loading.value = true;
  try {
    await Promise.all([
      salesStore.fetchInvoices(),
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

function editInvoice(invoice) {
  isEditing.value = true;
  editingId.value = invoice.id;
  form.value = {
    customer_id: invoice.customer_id,
    invoice_code: invoice.invoice_code,
    reference: invoice.reference || '',
    invoice_date: invoice.invoice_date,
    due_date: invoice.due_date,
    tax_amount: parseFloat(invoice.tax_amount || 0),
    discount_amount: parseFloat(invoice.discount_amount || 0),
    shipping_cost: parseFloat(invoice.shipping_cost || 0),
    payment_terms: invoice.payment_terms || '',
    notes: invoice.notes || '',
    items: invoice.items?.map(item => ({
      product_id: item.product_id,
      quantity: parseFloat(item.quantity),
      unit_price: parseFloat(item.unit_price)
    })) || []
  };
  modal.open();
}

function viewInvoice(invoice) {
  notify('View functionality coming soon', 'info');
}

async function sendInvoice(invoice) {
  if (!confirm(`Send invoice ${invoice.invoice_code} to customer?`)) return;
  
  try {
    await salesStore.sendInvoice(invoice.id);
    notify('Invoice sent successfully', 'success');
  } catch (error) {
    notify('Failed to send invoice', 'error');
  }
}

function recordPayment(invoice) {
  selectedInvoice.value = invoice;
  const balance = parseFloat(invoice.total_amount || 0) - parseFloat(invoice.paid_amount || 0);
  paymentForm.value = {
    amount: balance > 0 ? balance : 0,
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: '',
    transaction_reference: '',
    notes: ''
  };
  paymentErrors.value = {};
  paymentModal.open();
}

async function handlePaymentSubmit() {
  paymentErrors.value = {};
  
  if (!paymentForm.value.amount || paymentForm.value.amount <= 0) {
    paymentErrors.value.amount = 'Amount must be greater than 0';
    return;
  }
  
  if (!paymentForm.value.payment_method) {
    paymentErrors.value.payment_method = 'Payment method is required';
    return;
  }

  const balance = parseFloat(selectedInvoice.value?.total_amount || 0) - parseFloat(selectedInvoice.value?.paid_amount || 0);
  if (paymentForm.value.amount > balance) {
    paymentErrors.value.amount = 'Payment amount cannot exceed balance due';
    return;
  }

  saving.value = true;
  try {
    await salesStore.recordInvoicePayment(selectedInvoice.value.id, paymentForm.value);
    notify('Payment recorded successfully', 'success');
    paymentModal.close();
    selectedInvoice.value = null;
  } catch (error) {
    notify(error.message || 'Failed to record payment', 'error');
  } finally {
    saving.value = false;
  }
}

async function markAsPaid(invoice) {
  if (!confirm(`Mark invoice ${invoice.invoice_code} as paid?`)) return;
  
  try {
    await salesStore.markInvoiceAsPaid(invoice.id);
    notify('Invoice marked as paid', 'success');
  } catch (error) {
    notify('Failed to mark invoice as paid', 'error');
  }
}

async function deleteInvoice(invoice) {
  if (!confirm(`Delete invoice ${invoice.invoice_code}?`)) return;
  
  try {
    await salesStore.deleteInvoice(invoice.id);
    notify('Invoice deleted successfully', 'success');
  } catch (error) {
    notify('Failed to delete invoice', 'error');
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
      await salesStore.updateInvoice(editingId.value, data);
      notify('Invoice updated successfully', 'success');
    } else {
      await salesStore.createInvoice(data);
      notify('Invoice created successfully', 'success');
    }
    
    modal.close();
    resetForm();
  } catch (error) {
    notify(error.message || 'Failed to save invoice', 'error');
  } finally {
    saving.value = false;
  }
}

function resetForm() {
  form.value = {
    customer_id: '',
    invoice_code: '',
    reference: '',
    invoice_date: new Date().toISOString().split('T')[0],
    due_date: '',
    tax_amount: 0,
    discount_amount: 0,
    shipping_cost: 0,
    payment_terms: '',
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
    sent: 'info',
    unpaid: 'warning',
    partially_paid: 'primary',
    paid: 'success',
    overdue: 'danger',
    cancelled: 'secondary'
  };
  return variants[status] || 'secondary';
}

function formatStatus(status) {
  const labels = {
    draft: 'Draft',
    sent: 'Sent',
    unpaid: 'Unpaid',
    partially_paid: 'Partially Paid',
    paid: 'Paid',
    overdue: 'Overdue',
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
