<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Bills of Materials</h1>
      <button @click="openCreateModal" class="btn btn-primary">
        <PlusIcon class="icon" />
        Create BOM
      </button>
    </div>
    
    <div class="filters-section">
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search by BOM code or product..."
        class="search-input"
        @input="fetchBOMs"
      />
      <div class="filter-group">
        <select v-model="filters.status" @change="fetchBOMs" class="filter-select">
          <option value="">All Status</option>
          <option value="draft">Draft</option>
          <option value="active">Active</option>
          <option value="archived">Archived</option>
        </select>
        <select v-model="filters.product_id" @change="fetchBOMs" class="filter-select">
          <option value="">All Products</option>
          <option v-for="product in products" :key="product.id" :value="product.id">
            {{ product.name }}
          </option>
        </select>
      </div>
    </div>
    
    <DataTable
      :columns="columns"
      :data="boms"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #cell-bom_code="{ row }">
        <span class="font-semibold text-blue-600">{{ row.bom_code }}</span>
      </template>
      
      <template #cell-effective_date="{ row }">
        {{ formatDate(row.effective_date) }}
      </template>
      
      <template #cell-total_cost="{ row }">
        {{ formatCurrency(row.total_cost) }}
      </template>
      
      <template #cell-status="{ row }">
        <span :class="['badge', `badge-${getStatusColor(row.status)}`]">
          {{ row.status }}
        </span>
      </template>
      
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewBOM(row)" class="btn-icon" title="View">
            <EyeIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'draft'"
            @click="editBOM(row)" 
            class="btn-icon" 
            title="Edit"
          >
            <PencilIcon class="icon" />
          </button>
          <button 
            @click="cloneBOM(row)" 
            class="btn-icon" 
            title="Clone"
          >
            <DocumentDuplicateIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'draft'"
            @click="activateBOM(row)" 
            class="btn-icon btn-success" 
            title="Activate"
          >
            <CheckCircleIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'active'"
            @click="archiveBOM(row)" 
            class="btn-icon btn-warning" 
            title="Archive"
          >
            <ArchiveBoxIcon class="icon" />
          </button>
          <button 
            v-if="row.status === 'draft'"
            @click="deleteBOM(row)" 
            class="btn-icon btn-danger" 
            title="Delete"
          >
            <TrashIcon class="icon" />
          </button>
        </div>
      </template>
    </DataTable>
    
    <Modal
      :show="showModal"
      :title="isEditMode ? 'Edit BOM' : 'Create BOM'"
      size="large"
      @close="closeModal"
      @confirm="handleSaveBOM"
    >
      <div class="form-section">
        <div class="form-grid">
          <FormSelect
            v-model="form.product_id"
            label="Finished Product"
            :options="productOptions"
            required
          />
          <FormInput
            v-model="form.bom_code"
            label="BOM Code"
            placeholder="Auto-generated if empty"
          />
          <FormInput
            v-model="form.version"
            label="Version"
            type="text"
            required
          />
          <FormInput
            v-model.number="form.quantity"
            label="Output Quantity"
            type="number"
            min="1"
            required
          />
          <FormInput
            v-model="form.effective_date"
            label="Effective Date"
            type="date"
            required
          />
          <FormSelect
            v-model="form.status"
            label="Status"
            :options="statusOptions"
            required
          />
        </div>
        
        <div class="items-section">
          <div class="section-header">
            <h4>Components & Materials</h4>
            <button @click="addComponent" type="button" class="btn btn-sm btn-secondary">
              <PlusIcon class="icon-sm" />
              Add Component
            </button>
          </div>
          
          <div v-for="(component, index) in form.components" :key="index" class="item-row">
            <FormSelect
              v-model="component.product_id"
              label="Component"
              :options="componentOptions"
              required
            />
            <FormInput
              v-model.number="component.quantity"
              label="Quantity"
              type="number"
              min="0.01"
              step="0.01"
              required
            />
            <FormInput
              v-model="component.unit"
              label="Unit"
              placeholder="pcs, kg, L, etc."
              required
            />
            <FormInput
              v-model.number="component.waste_percentage"
              label="Waste %"
              type="number"
              min="0"
              max="100"
            />
            <FormInput
              v-model.number="component.unit_cost"
              label="Unit Cost"
              type="number"
              step="0.01"
              min="0"
              required
            />
            <div class="item-total">
              <label class="form-label">Total</label>
              <div class="total-value">{{ formatCurrency(calculateComponentTotal(component)) }}</div>
              <div v-if="component.product_id" class="availability-badge" :class="getAvailabilityClass(component)">
                {{ getAvailabilityText(component) }}
              </div>
            </div>
            <button @click="removeComponent(index)" type="button" class="btn-icon btn-danger" title="Remove">
              <TrashIcon class="icon" />
            </button>
          </div>
        </div>
        
        <div class="costs-section">
          <div class="section-header">
            <h4>Additional Costs</h4>
          </div>
          <div class="form-grid">
            <FormInput
              v-model.number="form.labor_cost"
              label="Labor Cost"
              type="number"
              step="0.01"
              min="0"
            />
            <FormInput
              v-model.number="form.overhead_cost"
              label="Overhead Cost"
              type="number"
              step="0.01"
              min="0"
            />
          </div>
        </div>
        
        <div class="totals-section">
          <div class="total-row">
            <span class="total-label">Material Cost:</span>
            <span class="total-value">{{ formatCurrency(calculateMaterialCost()) }}</span>
          </div>
          <div class="total-row">
            <span class="total-label">Labor Cost:</span>
            <span class="total-value">{{ formatCurrency(form.labor_cost || 0) }}</span>
          </div>
          <div class="total-row">
            <span class="total-label">Overhead Cost:</span>
            <span class="total-value">{{ formatCurrency(form.overhead_cost || 0) }}</span>
          </div>
          <div class="total-row grand-total">
            <span class="total-label">Total BOM Cost:</span>
            <span class="total-value">{{ formatCurrency(calculateTotalCost()) }}</span>
          </div>
          <div class="total-row">
            <span class="total-label">Cost per Unit:</span>
            <span class="total-value">{{ formatCurrency(calculateCostPerUnit()) }}</span>
          </div>
        </div>
        
        <FormTextarea
          v-model="form.notes"
          label="Notes"
          rows="3"
          placeholder="Additional notes or instructions"
        />
      </div>
    </Modal>
    
    <Modal
      :show="showViewModal"
      title="BOM Details"
      size="large"
      hide-footer
      @close="showViewModal = false"
    >
      <div v-if="selectedBOM" class="view-details">
        <div class="detail-header">
          <div>
            <h3>{{ selectedBOM.bom_code }} v{{ selectedBOM.version }}</h3>
            <p class="detail-subtitle">Product: {{ selectedBOM.product_name }}</p>
          </div>
          <span :class="['badge badge-lg', `badge-${getStatusColor(selectedBOM.status)}`]">
            {{ selectedBOM.status }}
          </span>
        </div>
        
        <div class="detail-grid">
          <div class="detail-item">
            <span class="detail-label">Output Quantity:</span>
            <span class="detail-value">{{ selectedBOM.quantity }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Effective Date:</span>
            <span class="detail-value">{{ formatDate(selectedBOM.effective_date) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Created By:</span>
            <span class="detail-value">{{ selectedBOM.created_by_name || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Created At:</span>
            <span class="detail-value">{{ formatDateTime(selectedBOM.created_at) }}</span>
          </div>
        </div>
        
        <div class="items-detail">
          <h4>Components & Materials</h4>
          <table class="detail-table">
            <thead>
              <tr>
                <th>Component</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Waste %</th>
                <th>Unit Cost</th>
                <th>Total Cost</th>
                <th>Availability</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="component in selectedBOM.components" :key="component.id">
                <td>{{ component.product_name }}</td>
                <td>{{ component.quantity }}</td>
                <td>{{ component.unit }}</td>
                <td>{{ component.waste_percentage }}%</td>
                <td>{{ formatCurrency(component.unit_cost) }}</td>
                <td>{{ formatCurrency(component.total_cost) }}</td>
                <td>
                  <span :class="['badge', getAvailabilityClass(component)]">
                    {{ getAvailabilityText(component) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div class="detail-totals">
          <div class="total-row">
            <span>Material Cost:</span>
            <span>{{ formatCurrency(selectedBOM.material_cost) }}</span>
          </div>
          <div class="total-row">
            <span>Labor Cost:</span>
            <span>{{ formatCurrency(selectedBOM.labor_cost) }}</span>
          </div>
          <div class="total-row">
            <span>Overhead Cost:</span>
            <span>{{ formatCurrency(selectedBOM.overhead_cost) }}</span>
          </div>
          <div class="total-row">
            <span class="font-semibold">Total BOM Cost:</span>
            <span class="font-semibold">{{ formatCurrency(selectedBOM.total_cost) }}</span>
          </div>
          <div class="total-row">
            <span>Cost per Unit:</span>
            <span>{{ formatCurrency(selectedBOM.cost_per_unit) }}</span>
          </div>
        </div>
        
        <div v-if="selectedBOM.notes" class="detail-section">
          <h4>Notes</h4>
          <p>{{ selectedBOM.notes }}</p>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { 
  PlusIcon, PencilIcon, TrashIcon, EyeIcon, CheckCircleIcon,
  DocumentDuplicateIcon, ArchiveBoxIcon
} from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import FormTextarea from '../../../components/forms/FormTextarea.vue'
import manufacturingService from '../../../services/manufacturingService'
import inventoryService from '../../../services/inventoryService'

const loading = ref(false)
const showModal = ref(false)
const showViewModal = ref(false)
const isEditMode = ref(false)
const boms = ref([])
const selectedBOM = ref(null)
const currentPage = ref(1)
const totalPages = ref(1)
const products = ref([])
const stockData = ref({})

const filters = ref({
  search: '',
  status: '',
  product_id: ''
})

const form = ref({
  product_id: '',
  bom_code: '',
  version: '1.0',
  quantity: 1,
  effective_date: new Date().toISOString().split('T')[0],
  status: 'draft',
  labor_cost: 0,
  overhead_cost: 0,
  notes: '',
  components: []
})

const columns = [
  { key: 'bom_code', label: 'BOM Code', sortable: true },
  { key: 'product_name', label: 'Product', sortable: true },
  { key: 'version', label: 'Version', sortable: true },
  { key: 'quantity', label: 'Output Qty', sortable: true },
  { key: 'total_cost', label: 'Total Cost', sortable: true },
  { key: 'effective_date', label: 'Effective Date', sortable: true },
  { key: 'status', label: 'Status' }
]

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'active', label: 'Active' },
  { value: 'archived', label: 'Archived' }
]

const productOptions = computed(() => 
  products.value
    .filter(p => p.type === 'inventory' || p.type === 'finished_goods')
    .map(p => ({ value: p.id, label: `${p.name} (${p.sku || 'N/A'})` }))
)

const componentOptions = computed(() => 
  products.value.map(p => ({ value: p.id, label: `${p.name} (${p.sku || 'N/A'})` }))
)

onMounted(async () => {
  await Promise.all([
    fetchBOMs(),
    fetchProducts()
  ])
})

const fetchBOMs = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      search: filters.value.search,
      status: filters.value.status,
      product_id: filters.value.product_id
    }
    const response = await manufacturingService.getBOMs(params)
    boms.value = response.data.data || []
    totalPages.value = response.data.meta?.last_page || 1
  } catch (error) {
    console.error('Failed to fetch BOMs:', error)
    boms.value = []
  } finally {
    loading.value = false
  }
}

const fetchProducts = async () => {
  try {
    const response = await inventoryService.getProducts()
    products.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch products:', error)
  }
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchBOMs()
}

const openCreateModal = () => {
  isEditMode.value = false
  resetForm()
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  resetForm()
}

const resetForm = () => {
  form.value = {
    product_id: '',
    bom_code: '',
    version: '1.0',
    quantity: 1,
    effective_date: new Date().toISOString().split('T')[0],
    status: 'draft',
    labor_cost: 0,
    overhead_cost: 0,
    notes: '',
    components: []
  }
}

const addComponent = () => {
  form.value.components.push({
    product_id: '',
    quantity: 1,
    unit: 'pcs',
    waste_percentage: 0,
    unit_cost: 0
  })
}

const removeComponent = (index) => {
  form.value.components.splice(index, 1)
}

const calculateComponentTotal = (component) => {
  const qty = (component.quantity || 0)
  const waste = 1 + ((component.waste_percentage || 0) / 100)
  const totalQty = qty * waste
  return totalQty * (component.unit_cost || 0)
}

const calculateMaterialCost = () => {
  return form.value.components.reduce((sum, comp) => sum + calculateComponentTotal(comp), 0)
}

const calculateTotalCost = () => {
  return calculateMaterialCost() + (form.value.labor_cost || 0) + (form.value.overhead_cost || 0)
}

const calculateCostPerUnit = () => {
  const total = calculateTotalCost()
  const qty = form.value.quantity || 1
  return total / qty
}

const getAvailabilityText = (component) => {
  const product = products.value.find(p => p.id == component.product_id)
  if (!product) return 'Unknown'
  const stock = product.stock_quantity || 0
  const required = component.quantity || 0
  return stock >= required ? 'Available' : `Short: ${(required - stock).toFixed(2)}`
}

const getAvailabilityClass = (component) => {
  const product = products.value.find(p => p.id == component.product_id)
  if (!product) return 'badge-gray'
  const stock = product.stock_quantity || 0
  const required = component.quantity || 0
  return stock >= required ? 'badge-green' : 'badge-red'
}

const handleSaveBOM = async () => {
  if (form.value.components.length === 0) {
    alert('Please add at least one component to the BOM.')
    return
  }
  
  try {
    const data = {
      ...form.value,
      material_cost: calculateMaterialCost(),
      total_cost: calculateTotalCost(),
      cost_per_unit: calculateCostPerUnit()
    }
    
    if (isEditMode.value) {
      await manufacturingService.updateBOM(form.value.id, data)
    } else {
      await manufacturingService.createBOM(data)
    }
    
    closeModal()
    await fetchBOMs()
  } catch (error) {
    console.error('Failed to save BOM:', error)
    alert('Failed to save BOM. Please try again.')
  }
}

const viewBOM = async (bom) => {
  try {
    const response = await manufacturingService.getBOM(bom.id)
    selectedBOM.value = response.data.data
    showViewModal.value = true
  } catch (error) {
    console.error('Failed to fetch BOM details:', error)
  }
}

const editBOM = async (bom) => {
  try {
    const response = await manufacturingService.getBOM(bom.id)
    const data = response.data.data
    form.value = {
      id: data.id,
      product_id: data.product_id,
      bom_code: data.bom_code,
      version: data.version,
      quantity: data.quantity,
      effective_date: data.effective_date,
      status: data.status,
      labor_cost: data.labor_cost || 0,
      overhead_cost: data.overhead_cost || 0,
      notes: data.notes || '',
      components: data.components || []
    }
    isEditMode.value = true
    showModal.value = true
  } catch (error) {
    console.error('Failed to fetch BOM:', error)
  }
}

const cloneBOM = async (bom) => {
  try {
    const response = await manufacturingService.getBOM(bom.id)
    const data = response.data.data
    form.value = {
      product_id: data.product_id,
      bom_code: '',
      version: '1.0',
      quantity: data.quantity,
      effective_date: new Date().toISOString().split('T')[0],
      status: 'draft',
      labor_cost: data.labor_cost || 0,
      overhead_cost: data.overhead_cost || 0,
      notes: data.notes ? `Cloned from ${data.bom_code}\n\n${data.notes}` : `Cloned from ${data.bom_code}`,
      components: (data.components || []).map(c => ({ ...c, id: undefined }))
    }
    isEditMode.value = false
    showModal.value = true
  } catch (error) {
    console.error('Failed to clone BOM:', error)
  }
}

const activateBOM = async (bom) => {
  if (confirm(`Activate BOM ${bom.bom_code}? This will make it the active version.`)) {
    try {
      await manufacturingService.updateBOM(bom.id, { status: 'active' })
      await fetchBOMs()
    } catch (error) {
      console.error('Failed to activate BOM:', error)
      alert('Failed to activate BOM. Please try again.')
    }
  }
}

const archiveBOM = async (bom) => {
  if (confirm(`Archive BOM ${bom.bom_code}? It will no longer be available for new production orders.`)) {
    try {
      await manufacturingService.updateBOM(bom.id, { status: 'archived' })
      await fetchBOMs()
    } catch (error) {
      console.error('Failed to archive BOM:', error)
      alert('Failed to archive BOM. Please try again.')
    }
  }
}

const deleteBOM = async (bom) => {
  if (confirm(`Delete BOM ${bom.bom_code}? This action cannot be undone.`)) {
    try {
      await manufacturingService.deleteBOM(bom.id)
      await fetchBOMs()
    } catch (error) {
      console.error('Failed to delete BOM:', error)
      alert('Failed to delete BOM. Please try again.')
    }
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

const formatDateTime = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}

const getStatusColor = (status) => {
  const colors = {
    draft: 'gray',
    active: 'green',
    archived: 'purple'
  }
  return colors[status] || 'gray'
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

.filter-select {
  padding: 10px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  background: white;
}

.filter-select:focus {
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

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background: #4b5563;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
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

.btn-icon.btn-success:hover {
  color: #10b981;
  background: #d1fae5;
}

.btn-icon.btn-warning:hover {
  color: #f59e0b;
  background: #fef3c7;
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

.badge-lg {
  padding: 6px 16px;
  font-size: 14px;
}

.badge-green {
  background: #dcfce7;
  color: #166534;
}

.badge-purple {
  background: #e9d5ff;
  color: #6b21a8;
}

.badge-red {
  background: #fee2e2;
  color: #991b1b;
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

.form-section {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.section-header h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.items-section, .costs-section {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
}

.item-row {
  display: grid;
  grid-template-columns: 2fr 1fr 0.8fr 0.8fr 1fr 1.2fr auto;
  gap: 12px;
  align-items: end;
  margin-bottom: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #f3f4f6;
}

.item-row:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.item-total {
  display: flex;
  flex-direction: column;
}

.form-label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.total-value {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 4px;
}

.availability-badge {
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 8px;
  text-align: center;
}

.totals-section {
  border-top: 2px solid #e5e7eb;
  padding-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-end;
}

.total-row {
  display: flex;
  gap: 24px;
  justify-content: space-between;
  min-width: 300px;
}

.total-label {
  font-weight: 500;
  color: #6b7280;
}

.grand-total {
  font-size: 18px;
  font-weight: 700;
  padding-top: 8px;
  border-top: 2px solid #e5e7eb;
}

.grand-total .total-label,
.grand-total .total-value {
  color: #1f2937;
}

.view-details {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  padding-bottom: 16px;
  border-bottom: 2px solid #e5e7eb;
}

.detail-header h3 {
  font-size: 24px;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 4px 0;
}

.detail-subtitle {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
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

.detail-label {
  font-size: 13px;
  font-weight: 500;
  color: #6b7280;
}

.detail-value {
  font-size: 14px;
  color: #1f2937;
}

.items-detail h4,
.detail-section h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 12px;
}

.detail-table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.detail-table thead {
  background: #f9fafb;
}

.detail-table th,
.detail-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
  font-size: 14px;
}

.detail-table th {
  font-weight: 600;
  color: #374151;
}

.detail-table td {
  color: #1f2937;
}

.detail-table tbody tr:last-child td {
  border-bottom: none;
}

.detail-totals {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: flex-end;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
}

.detail-section p {
  color: #4b5563;
  line-height: 1.6;
  margin: 0;
}
</style>
