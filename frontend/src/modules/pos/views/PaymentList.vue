<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Payments</h1>
      <button @click="openCreateModal" class="btn btn-primary">
        <PlusIcon class="icon" />
        Record Payment
      </button>
    </div>
    
    <div class="filters-section">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by customer or payment number..."
        class="search-input"
        @input="fetchPayments"
      />
      <div class="filter-group">
        <select v-model="filters.paymentMethod" @change="fetchPayments" class="filter-select">
          <option value="">All Payment Methods</option>
          <option value="cash">Cash</option>
          <option value="credit_card">Credit Card</option>
          <option value="debit_card">Debit Card</option>
          <option value="bank_transfer">Bank Transfer</option>
          <option value="check">Check</option>
          <option value="mobile_payment">Mobile Payment</option>
        </select>
        <input
          v-model="filters.dateFrom"
          type="date"
          class="filter-input"
          @change="fetchPayments"
          placeholder="From Date"
        />
        <input
          v-model="filters.dateTo"
          type="date"
          class="filter-input"
          @change="fetchPayments"
          placeholder="To Date"
        />
      </div>
    </div>
    
    <DataTable
      :columns="columns"
      :data="payments"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-payment_number="{ row }">
        <span class="font-semibold text-blue-600">{{ row.payment_number }}</span>
      </template>
      
      <template #cell-payment_date="{ row }">
        {{ formatDate(row.payment_date) }}
      </template>
      
      <template #cell-amount="{ row }">
        <span class="font-semibold text-green-600">{{ formatCurrency(row.amount) }}</span>
      </template>
      
      <template #cell-payment_method="{ row }">
        <span :class="['badge', `badge-${getPaymentMethodColor(row.payment_method)}`]">
          {{ formatPaymentMethod(row.payment_method) }}
        </span>
      </template>
      
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewPayment(row)" class="btn-icon" title="View">
            <EyeIcon class="icon" />
          </button>
          <button @click="editPayment(row)" class="btn-icon" title="Edit">
            <PencilIcon class="icon" />
          </button>
          <button @click="printReceipt(row)" class="btn-icon" title="Print Receipt">
            <PrinterIcon class="icon" />
          </button>
          <button @click="deletePaymentConfirm(row)" class="btn-icon btn-danger" title="Delete">
            <TrashIcon class="icon" />
          </button>
        </div>
      </template>
    </DataTable>
    
    <Modal
      :show="showModal"
      :title="isEditMode ? 'Edit Payment' : 'Record Payment'"
      size="medium"
      @close="closeModal"
      @confirm="handleSavePayment"
    >
      <div class="form-section">
        <FormSelect
          v-model="form.invoice_id"
          label="Invoice"
          :options="invoiceOptions"
          required
          @update:modelValue="updateInvoiceDetails"
        />
        
        <div v-if="selectedInvoiceDetails" class="invoice-details">
          <div class="detail-row">
            <span class="detail-label">Customer:</span>
            <span class="detail-value">{{ selectedInvoiceDetails.customer_name }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Invoice Total:</span>
            <span class="detail-value">{{ formatCurrency(selectedInvoiceDetails.total_amount) }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Paid:</span>
            <span class="detail-value">{{ formatCurrency(selectedInvoiceDetails.paid_amount || 0) }}</span>
          </div>
          <div class="detail-row outstanding">
            <span class="detail-label">Outstanding:</span>
            <span class="detail-value">{{ formatCurrency(selectedInvoiceDetails.total_amount - (selectedInvoiceDetails.paid_amount || 0)) }}</span>
          </div>
        </div>
        
        <div class="form-grid">
          <FormInput
            v-model.number="form.amount"
            label="Payment Amount"
            type="number"
            step="0.01"
            min="0"
            :max="selectedInvoiceDetails ? selectedInvoiceDetails.total_amount - (selectedInvoiceDetails.paid_amount || 0) : undefined"
            required
          />
          <FormInput
            v-model="form.payment_date"
            label="Payment Date"
            type="date"
            required
          />
          <FormSelect
            v-model="form.payment_method"
            label="Payment Method"
            :options="paymentMethodOptions"
            required
          />
          <FormInput
            v-model="form.reference_number"
            label="Reference Number"
            placeholder="e.g., Check #, Transaction ID"
          />
        </div>
        
        <FormTextarea
          v-model="form.notes"
          label="Notes"
          rows="3"
        />
      </div>
    </Modal>
    
    <Modal
      :show="showViewModal"
      title="Payment Details"
      size="medium"
      hide-footer
      @close="showViewModal = false"
    >
      <div v-if="selectedPayment" class="view-details">
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Payment Number:</span>
            <span class="detail-value">{{ selectedPayment.payment_number }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Customer:</span>
            <span class="detail-value">{{ selectedPayment.customer_name }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Invoice:</span>
            <span class="detail-value">{{ selectedPayment.invoice_number }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Payment Date:</span>
            <span class="detail-value">{{ formatDate(selectedPayment.payment_date) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Amount:</span>
            <span class="detail-value font-semibold text-green-600">{{ formatCurrency(selectedPayment.amount) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Payment Method:</span>
            <span :class="['badge', `badge-${getPaymentMethodColor(selectedPayment.payment_method)}`]">
              {{ formatPaymentMethod(selectedPayment.payment_method) }}
            </span>
          </div>
          <div class="detail-item" v-if="selectedPayment.reference_number">
            <span class="detail-label">Reference Number:</span>
            <span class="detail-value">{{ selectedPayment.reference_number }}</span>
          </div>
        </div>
        
        <div v-if="selectedPayment.notes" class="detail-section">
          <h4>Notes</h4>
          <p>{{ selectedPayment.notes }}</p>
        </div>
        
        <div class="action-section">
          <button @click="printReceipt(selectedPayment)" class="btn btn-primary">
            <PrinterIcon class="icon-sm" />
            Print Receipt
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon, PrinterIcon } from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'
import posService from '../../../services/posService'

const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const payments = ref([])
const selectedPayment = ref(null)
const invoices = ref([])
const selectedInvoiceDetails = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)

const filters = ref({
  search: '',
  paymentMethod: '',
  dateFrom: '',
  dateTo: ''
})

const form = ref({
  invoice_id: '',
  amount: 0,
  payment_date: new Date().toISOString().split('T')[0],
  payment_method: 'cash',
  reference_number: '',
  notes: ''
})

const columns = [
  { key: 'payment_number', label: 'Payment #', sortable: true },
  { key: 'payment_date', label: 'Date', sortable: true },
  { key: 'customer_name', label: 'Customer', sortable: true },
  { key: 'invoice_number', label: 'Invoice', sortable: true },
  { key: 'amount', label: 'Amount', sortable: true },
  { key: 'payment_method', label: 'Payment Method' },
  { key: 'reference_number', label: 'Reference' }
]

const paymentMethodOptions = [
  { value: 'cash', label: 'Cash' },
  { value: 'credit_card', label: 'Credit Card' },
  { value: 'debit_card', label: 'Debit Card' },
  { value: 'bank_transfer', label: 'Bank Transfer' },
  { value: 'check', label: 'Check' },
  { value: 'mobile_payment', label: 'Mobile Payment' }
]

const invoiceOptions = computed(() => 
  invoices.value
    .filter(inv => inv.status !== 'paid' && inv.status !== 'cancelled')
    .map(inv => ({ 
      value: inv.id, 
      label: `${inv.invoice_number} - ${inv.customer_name} (${formatCurrency((inv.total_amount || 0) - (inv.paid_amount || 0))})` 
    }))
)

onMounted(async () => {
  await Promise.all([
    fetchPayments(),
    fetchInvoices()
  ])
})

const fetchPayments = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      search: filters.value.search,
      payment_method: filters.value.paymentMethod,
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo
    }
    const response = await posService.getPayments(params)
    payments.value = response.data.data || []
    totalPages.value = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch payments:', error)
    payments.value = []
  } finally {
    loading.value = false
  }
}

const fetchInvoices = async () => {
  try {
    const response = await posService.getInvoices({ per_page: 1000 })
    invoices.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch invoices:', error)
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchPayments()
}

const openCreateModal = () => {
  isEditMode.value = false
  resetForm()
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedInvoiceDetails.value = null
  resetForm()
}

const resetForm = () => {
  form.value = {
    invoice_id: '',
    amount: 0,
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    reference_number: '',
    notes: ''
  }
}

const updateInvoiceDetails = async (invoiceId) => {
  if (!invoiceId) {
    selectedInvoiceDetails.value = null
    return
  }
  
  try {
    const response = await posService.getInvoice(invoiceId)
    selectedInvoiceDetails.value = response.data.data
    form.value.amount = selectedInvoiceDetails.value.total_amount - (selectedInvoiceDetails.value.paid_amount || 0)
  } catch (error) {
    console.error('Failed to fetch invoice details:', error)
  }
}

const handleSavePayment = async () => {
  try {
    const data = {
      ...form.value,
      customer_id: selectedInvoiceDetails.value?.customer_id
    }
    
    await posService.createPayment(data)
    closeModal()
    await fetchPayments()
    await fetchInvoices()
    alert('Payment recorded successfully!')
  } catch (error) {
    console.error('Failed to save payment:', error)
    alert('Failed to save payment. Please try again.')
  }
}

const viewPayment = async (payment) => {
  selectedPayment.value = payment
  showViewModal.value = true
}

const editPayment = async (payment) => {
  try {
    form.value = {
      id: payment.id,
      invoice_id: payment.invoice_id,
      amount: payment.amount,
      payment_date: payment.payment_date,
      payment_method: payment.payment_method,
      reference_number: payment.reference_number || '',
      notes: payment.notes || ''
    }
    await updateInvoiceDetails(payment.invoice_id)
    isEditMode.value = true
    showModal.value = true
  } catch (error) {
    console.error('Failed to load payment:', error)
  }
}

const deletePaymentConfirm = async (payment) => {
  if (confirm(`Are you sure you want to delete payment ${payment.payment_number}?`)) {
    try {
      await posService.deletePayment(payment.id)
      await fetchPayments()
      alert('Payment deleted successfully')
    } catch (error) {
      console.error('Failed to delete payment:', error)
      alert('Failed to delete payment. This operation may not be supported by the backend.')
    }
  }
}

const printReceipt = async (payment) => {
  try {
    const { generateReceiptPDF } = await import('@/utils/pdfGenerator')
    await generateReceiptPDF(payment)
  } catch (err) {
    console.error('Error printing receipt:', err)
    alert('Failed to print receipt')
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

const formatPaymentMethod = (method) => {
  const methods = {
    cash: 'Cash',
    credit_card: 'Credit Card',
    debit_card: 'Debit Card',
    bank_transfer: 'Bank Transfer',
    check: 'Check',
    mobile_payment: 'Mobile Payment'
  }
  return methods[method] || method
}

const getPaymentMethodColor = (method) => {
  const colors = {
    cash: 'green',
    credit_card: 'blue',
    debit_card: 'purple',
    bank_transfer: 'indigo',
    check: 'orange',
    mobile_payment: 'pink'
  }
  return colors[method] || 'gray'
}
</script>

<style scoped>
.page-container {
  max-width: 1400px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
}

.filters-section {
  margin-bottom: 20px;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  min-width: 300px;
  padding: 10px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-group {
  display: flex;
  gap: 12px;
}

.filter-select,
.filter-input {
  padding: 10px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  background: white;
}

.filter-select:focus,
.filter-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.icon {
  width: 20px;
  height: 20px;
}

.icon-sm {
  width: 16px;
  height: 16px;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
  color: #6b7280;
  transition: color 0.2s;
  border-radius: 4px;
}

.btn-icon:hover {
  color: #3b82f6;
  background: #eff6ff;
}

.btn-icon.btn-danger:hover {
  color: #ef4444;
  background: #fef2f2;
}

.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  text-transform: capitalize;
}

.badge-blue {
  background: #dbeafe;
  color: #1e40af;
}

.badge-green {
  background: #dcfce7;
  color: #166534;
}

.badge-purple {
  background: #f3e8ff;
  color: #6b21a8;
}

.badge-orange {
  background: #ffedd5;
  color: #9a3412;
}

.badge-indigo {
  background: #e0e7ff;
  color: #3730a3;
}

.badge-pink {
  background: #fce7f3;
  color: #9f1239;
}

.badge-gray {
  background: #f3f4f6;
  color: #374151;
}

.font-semibold {
  font-weight: 600;
}

.text-blue-600 {
  color: #2563eb;
}

.text-green-600 {
  color: #16a34a;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.invoice-details {
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  border: 1px solid #e5e7eb;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-label {
  font-weight: 500;
  color: #6b7280;
  font-size: 14px;
}

.detail-value {
  font-weight: 600;
  color: #1f2937;
  font-size: 14px;
}

.detail-row.outstanding {
  padding-top: 8px;
  margin-top: 8px;
  border-top: 1px solid #e5e7eb;
  font-size: 15px;
}

.detail-row.outstanding .detail-value {
  color: #dc2626;
}

.view-details {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-item .detail-label {
  font-size: 13px;
  font-weight: 500;
  color: #6b7280;
}

.detail-item .detail-value {
  font-size: 14px;
  color: #1f2937;
}

.detail-section h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 12px;
}

.detail-section p {
  color: #4b5563;
  line-height: 1.6;
  margin: 0;
}

.action-section {
  display: flex;
  justify-content: flex-end;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}
</style>
