<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Stock Ledger</h1>
    </div>
    
    <div class="filters-section">
      <div class="filter-group">
        <select v-model="filters.product_id" @change="fetchLedger" class="filter-select">
          <option value="">All Products</option>
          <option v-for="product in products" :key="product.id" :value="product.id">
            {{ product.name }} ({{ product.sku }})
          </option>
        </select>
        <select v-model="filters.transaction_type" @change="fetchLedger" class="filter-select">
          <option value="">All Transaction Types</option>
          <option value="purchase">Purchase</option>
          <option value="sale">Sale</option>
          <option value="adjustment">Adjustment</option>
          <option value="transfer">Transfer</option>
          <option value="return">Return</option>
        </select>
        <input v-model="filters.dateFrom" type="date" class="filter-input" @change="fetchLedger" placeholder="From Date" />
        <input v-model="filters.dateTo" type="date" class="filter-input" @change="fetchLedger" placeholder="To Date" />
      </div>
    </div>
    
    <DataTable :columns="columns" :data="ledgerEntries" :loading="loading" :current-page="currentPage" :total-pages="totalPages" @page-change="handlePageChange">
      <template #cell-transaction_type="{ row }">
        <span :class="['badge', `badge-${getTransactionColor(row.transaction_type)}`]">{{ row.transaction_type }}</span>
      </template>
      <template #cell-quantity="{ row }">
        <span :class="row.quantity > 0 ? 'text-green' : 'text-red'">{{ row.quantity > 0 ? '+' : '' }}{{ row.quantity }}</span>
      </template>
      <template #cell-running_balance="{ row }">
        <span class="font-semibold">{{ row.running_balance }}</span>
      </template>
      <template #cell-created_at="{ row }">{{ formatDateTime(row.created_at) }}</template>
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewDetails(row)" class="btn-icon" title="View Details"><EyeIcon class="icon" /></button>
        </div>
      </template>
    </DataTable>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { EyeIcon } from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'

const loading = ref(false)
const ledgerEntries = ref([])
const products = ref([])
const currentPage = ref(1)
const totalPages = ref(1)
const filters = ref({ product_id: '', transaction_type: '', dateFrom: '', dateTo: '' })
const columns = [
  { key: 'product_name', label: 'Product', sortable: true },
  { key: 'transaction_type', label: 'Transaction Type' },
  { key: 'quantity', label: 'Quantity' },
  { key: 'running_balance', label: 'Balance' },
  { key: 'reference', label: 'Reference' },
  { key: 'created_at', label: 'Date', sortable: true }
]

onMounted(() => { fetchLedger() })
const fetchLedger = async () => { loading.value = true; ledgerEntries.value = []; loading.value = false }
const handlePageChange = (page) => { currentPage.value = page; fetchLedger() }
const viewDetails = (entry) => {}
const getTransactionColor = (type) => ({ purchase: 'green', sale: 'blue', adjustment: 'orange', transfer: 'purple', return: 'gray' }[type] || 'gray')
const formatDateTime = (date) => date ? new Date(date).toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'N/A'
</script>

<style scoped>
.page-container { max-width: 1400px; }
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.page-title { font-size: 28px; font-weight: 700; color: #1f2937; }
.filters-section { margin-bottom: 20px; }
.filter-group { display: flex; gap: 12px; flex-wrap: wrap; }
.filter-select, .filter-input { padding: 10px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; background: white; }
.badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; text-transform: capitalize; }
.badge-green { background: #dcfce7; color: #166534; }
.badge-blue { background: #dbeafe; color: #1e40af; }
.badge-orange { background: #ffedd5; color: #9a3412; }
.badge-purple { background: #f3e8ff; color: #6b21a8; }
.badge-gray { background: #f3f4f6; color: #374151; }
.text-green { color: #16a34a; font-weight: 600; }
.text-red { color: #ef4444; font-weight: 600; }
.font-semibold { font-weight: 600; }
.action-buttons { display: flex; gap: 8px; }
.btn-icon { background: none; border: none; cursor: pointer; padding: 6px; color: #6b7280; transition: color 0.2s; border-radius: 4px; }
.btn-icon:hover { color: #3b82f6; background: #eff6ff; }
.icon { width: 20px; height: 20px; }
</style>
