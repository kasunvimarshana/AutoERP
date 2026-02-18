<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Dashboards</h1>
      <button @click="showCreateModal = true" class="btn btn-primary">
        <PlusIcon class="icon" />
        Create Dashboard
      </button>
    </div>
    
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading dashboards...</p>
    </div>
    
    <div v-else-if="error" class="error-state">
      <p class="error-message">{{ error }}</p>
      <button @click="fetchDashboards" class="btn btn-primary">Retry</button>
    </div>
    
    <div v-else class="dashboards-grid">
      <!-- Predefined Dashboards -->
      <div 
        v-for="dashboard in predefinedDashboards" 
        :key="dashboard.id"
        class="dashboard-card"
        @click="viewDashboard(dashboard)"
      >
        <div class="dashboard-icon" :style="{ background: dashboard.color }">
          <component :is="dashboard.icon" class="icon-large" />
        </div>
        <div class="dashboard-content">
          <h3 class="dashboard-name">{{ dashboard.name }}</h3>
          <p class="dashboard-description">{{ dashboard.description }}</p>
          <div class="dashboard-meta">
            <span class="meta-item">
              <ClockIcon class="icon-small" />
              Updated {{ dashboard.lastUpdated }}
            </span>
            <span class="meta-item">
              <ChartBarIcon class="icon-small" />
              {{ dashboard.widgetCount }} widgets
            </span>
          </div>
        </div>
        <div class="dashboard-actions" @click.stop>
          <button @click="viewDashboard(dashboard)" class="btn-action" title="View">
            <EyeIcon class="icon-small" />
          </button>
          <button @click="shareDashboard(dashboard)" class="btn-action" title="Share">
            <ShareIcon class="icon-small" />
          </button>
        </div>
      </div>
      
      <!-- Custom Dashboards -->
      <div 
        v-for="dashboard in customDashboards" 
        :key="dashboard.id"
        class="dashboard-card custom"
        @click="viewDashboard(dashboard)"
      >
        <div class="dashboard-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
          <RectangleStackIcon class="icon-large" />
        </div>
        <div class="dashboard-content">
          <h3 class="dashboard-name">{{ dashboard.name }}</h3>
          <p class="dashboard-description">{{ dashboard.description }}</p>
          <div class="dashboard-meta">
            <span class="meta-item">
              <ClockIcon class="icon-small" />
              Updated {{ formatDate(dashboard.updated_at) }}
            </span>
            <span class="meta-item">
              <ChartBarIcon class="icon-small" />
              {{ dashboard.widget_count || 0 }} widgets
            </span>
          </div>
        </div>
        <div class="dashboard-actions" @click.stop>
          <button @click="viewDashboard(dashboard)" class="btn-action" title="View">
            <EyeIcon class="icon-small" />
          </button>
          <button @click="editDashboard(dashboard)" class="btn-action" title="Edit">
            <PencilIcon class="icon-small" />
          </button>
          <button @click="shareDashboard(dashboard)" class="btn-action" title="Share">
            <ShareIcon class="icon-small" />
          </button>
          <button @click="deleteDashboard(dashboard)" class="btn-action danger" title="Delete">
            <TrashIcon class="icon-small" />
          </button>
        </div>
      </div>
    </div>
    
    <!-- Create/Edit Dashboard Modal -->
    <Modal
      :show="showCreateModal || showEditModal"
      :title="editingDashboard ? 'Edit Dashboard' : 'Create Dashboard'"
      size="large"
      @close="closeModal"
      @confirm="handleSaveDashboard"
    >
      <div class="form-grid">
        <FormInput 
          v-model="dashboardForm.name" 
          label="Dashboard Name" 
          required 
          placeholder="e.g., Q1 Sales Dashboard"
        />
        <FormInput 
          v-model="dashboardForm.description" 
          label="Description" 
          placeholder="Brief description of the dashboard"
        />
        <FormSelect
          v-model="dashboardForm.layout"
          label="Layout"
          :options="layoutOptions"
          required
        />
        <FormSelect
          v-model="dashboardForm.refresh_interval"
          label="Refresh Interval"
          :options="refreshIntervalOptions"
        />
      </div>
      
      <div class="section-divider">
        <h4 class="section-title">Widgets</h4>
        <p class="section-description">Select widgets to include in your dashboard</p>
      </div>
      
      <div class="widgets-grid">
        <div 
          v-for="widget in availableWidgets" 
          :key="widget.id"
          class="widget-option"
          :class="{ selected: isWidgetSelected(widget.id) }"
          @click="toggleWidget(widget.id)"
        >
          <component :is="widget.icon" class="widget-icon" />
          <span class="widget-name">{{ widget.name }}</span>
        </div>
      </div>
      
      <div class="section-divider">
        <h4 class="section-title">Access Permissions</h4>
      </div>
      
      <div class="form-grid">
        <FormSelect
          v-model="dashboardForm.visibility"
          label="Visibility"
          :options="visibilityOptions"
        />
      </div>
    </Modal>
    
    <!-- View Dashboard Modal -->
    <Modal
      :show="showViewModal"
      :title="viewingDashboard?.name || 'Dashboard'"
      size="xlarge"
      hide-footer
      @close="showViewModal = false"
    >
      <div v-if="viewingDashboard" class="dashboard-viewer">
        <div class="viewer-toolbar">
          <div class="date-range-selector">
            <label>Date Range:</label>
            <select v-model="dateRange" class="range-select">
              <option value="today">Today</option>
              <option value="week">This Week</option>
              <option value="month">This Month</option>
              <option value="quarter">This Quarter</option>
              <option value="year">This Year</option>
              <option value="custom">Custom Range</option>
            </select>
          </div>
          
          <div class="viewer-actions">
            <button @click="refreshDashboardData" class="btn btn-secondary btn-sm">
              <ArrowPathIcon class="icon-small" />
              Refresh
            </button>
            <button @click="exportDashboard('pdf')" class="btn btn-secondary btn-sm">
              <DocumentArrowDownIcon class="icon-small" />
              Export PDF
            </button>
            <button @click="exportDashboard('image')" class="btn btn-secondary btn-sm">
              <PhotoIcon class="icon-small" />
              Export Image
            </button>
          </div>
        </div>
        
        <!-- Dashboard Grid Layout -->
        <div class="dashboard-grid" :class="`layout-${dashboardForm.layout || 'grid-2'}`">
          <div 
            v-for="widget in selectedWidgetsList" 
            :key="widget.id"
            class="widget-container"
          >
            <div class="widget-header">
              <h4>{{ widget.name }}</h4>
            </div>
            <div class="widget-body">
              <!-- Chart Placeholder -->
              <div class="chart-placeholder">
                <component :is="widget.icon" class="placeholder-icon" />
                <p class="placeholder-text">{{ widget.chartType }}: {{ widget.name }}</p>
                <p class="placeholder-note">Chart visualization will render here with live data</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Note about drag-and-drop -->
        <div class="feature-note">
          <p><strong>TODO:</strong> Drag-and-drop widget arrangement can be implemented using libraries like Vue Draggable or SortableJS</p>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { 
  PlusIcon, 
  EyeIcon, 
  PencilIcon, 
  TrashIcon, 
  ShareIcon,
  ClockIcon,
  ChartBarIcon,
  RectangleStackIcon,
  ArrowPathIcon,
  DocumentArrowDownIcon,
  PhotoIcon,
  ChartPieIcon,
  CurrencyDollarIcon,
  ShoppingCartIcon,
  CubeIcon,
  UsersIcon,
  TruckIcon
} from '@heroicons/vue/24/outline'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import reportingService from '../../../services/reportingService'

const loading = ref(false)
const error = ref(null)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showViewModal = ref(false)
const editingDashboard = ref(null)
const viewingDashboard = ref(null)
const customDashboards = ref([])
const dateRange = ref('month')

const dashboardForm = ref({
  name: '',
  description: '',
  layout: 'grid-2',
  refresh_interval: '300',
  widgets: [],
  visibility: 'private'
})

const predefinedDashboards = ref([
  {
    id: 'executive',
    name: 'Executive Dashboard',
    description: 'High-level metrics and KPIs for executive overview',
    icon: ChartBarIcon,
    color: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    lastUpdated: '2 hours ago',
    widgetCount: 8,
    type: 'predefined'
  },
  {
    id: 'sales',
    name: 'Sales Dashboard',
    description: 'Sales performance, revenue trends, and conversion metrics',
    icon: CurrencyDollarIcon,
    color: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    lastUpdated: '1 hour ago',
    widgetCount: 12,
    type: 'predefined'
  },
  {
    id: 'inventory',
    name: 'Inventory Dashboard',
    description: 'Stock levels, turnover rates, and inventory valuation',
    icon: CubeIcon,
    color: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    lastUpdated: '30 minutes ago',
    widgetCount: 10,
    type: 'predefined'
  },
  {
    id: 'finance',
    name: 'Finance Dashboard',
    description: 'Cash flow, P&L, accounts receivable and payable',
    icon: ChartPieIcon,
    color: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    lastUpdated: '3 hours ago',
    widgetCount: 9,
    type: 'predefined'
  },
  {
    id: 'operations',
    name: 'Operations Dashboard',
    description: 'Production, fulfillment, and operational efficiency',
    icon: TruckIcon,
    color: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    lastUpdated: '1 hour ago',
    widgetCount: 11,
    type: 'predefined'
  }
])

const layoutOptions = [
  { value: 'grid-1', label: 'Single Column' },
  { value: 'grid-2', label: 'Two Columns' },
  { value: 'grid-3', label: 'Three Columns' },
  { value: 'grid-4', label: 'Four Columns' }
]

const refreshIntervalOptions = [
  { value: '60', label: 'Every Minute' },
  { value: '300', label: 'Every 5 Minutes' },
  { value: '900', label: 'Every 15 Minutes' },
  { value: '1800', label: 'Every 30 Minutes' },
  { value: '3600', label: 'Every Hour' },
  { value: '0', label: 'Manual Only' }
]

const visibilityOptions = [
  { value: 'private', label: 'Private (Only Me)' },
  { value: 'team', label: 'Team' },
  { value: 'organization', label: 'Organization' },
  { value: 'public', label: 'Public' }
]

const availableWidgets = [
  { id: 'revenue-trend', name: 'Revenue Trend', icon: ChartBarIcon, chartType: 'Line Chart' },
  { id: 'sales-by-category', name: 'Sales by Category', icon: ChartPieIcon, chartType: 'Pie Chart' },
  { id: 'top-products', name: 'Top Products', icon: ShoppingCartIcon, chartType: 'Bar Chart' },
  { id: 'stock-levels', name: 'Stock Levels', icon: CubeIcon, chartType: 'Bar Chart' },
  { id: 'customer-growth', name: 'Customer Growth', icon: UsersIcon, chartType: 'Area Chart' },
  { id: 'order-volume', name: 'Order Volume', icon: TruckIcon, chartType: 'Line Chart' },
  { id: 'kpi-revenue', name: 'Total Revenue KPI', icon: CurrencyDollarIcon, chartType: 'KPI Card' },
  { id: 'kpi-orders', name: 'Total Orders KPI', icon: ShoppingCartIcon, chartType: 'KPI Card' }
]

const selectedWidgetsList = computed(() => {
  return availableWidgets.filter(w => dashboardForm.value.widgets.includes(w.id))
})

onMounted(() => {
  fetchDashboards()
})

const fetchDashboards = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await reportingService.getDashboards()
    customDashboards.value = response.data.data || []
  } catch (err) {
    error.value = 'Failed to load dashboards. Please try again.'
    console.error('Failed to fetch dashboards:', err)
  } finally {
    loading.value = false
  }
}

const viewDashboard = (dashboard) => {
  viewingDashboard.value = dashboard
  
  if (dashboard.type === 'predefined') {
    switch (dashboard.id) {
      case 'executive':
        dashboardForm.value.widgets = ['kpi-revenue', 'kpi-orders', 'revenue-trend', 'sales-by-category']
        break
      case 'sales':
        dashboardForm.value.widgets = ['revenue-trend', 'sales-by-category', 'top-products', 'customer-growth']
        break
      case 'inventory':
        dashboardForm.value.widgets = ['stock-levels', 'top-products', 'order-volume']
        break
      case 'finance':
        dashboardForm.value.widgets = ['kpi-revenue', 'revenue-trend', 'sales-by-category']
        break
      case 'operations':
        dashboardForm.value.widgets = ['order-volume', 'stock-levels', 'top-products']
        break
    }
  } else {
    dashboardForm.value.widgets = dashboard.widgets || []
  }
  
  showViewModal.value = true
}

const editDashboard = (dashboard) => {
  editingDashboard.value = dashboard
  dashboardForm.value = {
    name: dashboard.name,
    description: dashboard.description,
    layout: dashboard.layout || 'grid-2',
    refresh_interval: dashboard.refresh_interval?.toString() || '300',
    widgets: dashboard.widgets || [],
    visibility: dashboard.visibility || 'private'
  }
  showEditModal.value = true
}

const deleteDashboard = async (dashboard) => {
  if (confirm(`Are you sure you want to delete "${dashboard.name}"?`)) {
    try {
      await reportingService.deleteDashboard(dashboard.id)
      await fetchDashboards()
    } catch (err) {
      alert('Failed to delete dashboard')
      console.error('Failed to delete dashboard:', err)
    }
  }
}

const shareDashboard = (dashboard) => {
  alert(`Share functionality for "${dashboard.name}" will be implemented with share links and permissions`)
}

const handleSaveDashboard = async () => {
  try {
    if (editingDashboard.value) {
      await reportingService.updateDashboard(editingDashboard.value.id, dashboardForm.value)
    } else {
      await reportingService.createDashboard(dashboardForm.value)
    }
    closeModal()
    await fetchDashboards()
  } catch (err) {
    alert('Failed to save dashboard')
    console.error('Failed to save dashboard:', err)
  }
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingDashboard.value = null
  dashboardForm.value = {
    name: '',
    description: '',
    layout: 'grid-2',
    refresh_interval: '300',
    widgets: [],
    visibility: 'private'
  }
}

const isWidgetSelected = (widgetId) => {
  return dashboardForm.value.widgets.includes(widgetId)
}

const toggleWidget = (widgetId) => {
  const index = dashboardForm.value.widgets.indexOf(widgetId)
  if (index > -1) {
    dashboardForm.value.widgets.splice(index, 1)
  } else {
    dashboardForm.value.widgets.push(widgetId)
  }
}

const refreshDashboardData = () => {
  alert('Refreshing dashboard data...')
}

const exportDashboard = (format) => {
  alert(`Exporting dashboard as ${format.toUpperCase()}...`)
}

const formatDate = (dateString) => {
  if (!dateString) return 'Never'
  const date = new Date(dateString)
  const now = new Date()
  const diff = now - date
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)
  
  if (minutes < 60) return `${minutes} minutes ago`
  if (hours < 24) return `${hours} hours ago`
  if (days < 7) return `${days} days ago`
  return date.toLocaleDateString()
}
</script>

<style scoped>
.page-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 32px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
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
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 13px;
}

.icon {
  width: 20px;
  height: 20px;
}

.icon-small {
  width: 16px;
  height: 16px;
}

.icon-large {
  width: 32px;
  height: 32px;
}

.loading-state,
.error-state {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 16px;
  border: 3px solid #f3f4f6;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-message {
  color: #dc2626;
  margin-bottom: 16px;
}

.dashboards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 24px;
}

.dashboard-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  gap: 16px;
  position: relative;
}

.dashboard-card:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.dashboard-icon {
  width: 64px;
  height: 64px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.dashboard-content {
  flex: 1;
  min-width: 0;
}

.dashboard-name {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 8px;
}

.dashboard-description {
  font-size: 14px;
  color: #6b7280;
  margin-bottom: 12px;
  line-height: 1.5;
}

.dashboard-meta {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #9ca3af;
}

.dashboard-actions {
  display: flex;
  gap: 8px;
  position: absolute;
  top: 16px;
  right: 16px;
  opacity: 0;
  transition: opacity 0.2s;
}

.dashboard-card:hover .dashboard-actions {
  opacity: 1;
}

.btn-action {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: none;
  background: #f3f4f6;
  color: #6b7280;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-action:hover {
  background: #e5e7eb;
  color: #374151;
}

.btn-action.danger:hover {
  background: #fee2e2;
  color: #dc2626;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.section-divider {
  margin: 32px 0 20px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 8px;
}

.section-description {
  font-size: 14px;
  color: #6b7280;
  margin-bottom: 16px;
}

.widgets-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 12px;
  margin-bottom: 24px;
}

.widget-option {
  padding: 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  text-align: center;
  transition: all 0.2s;
}

.widget-option:hover {
  border-color: #3b82f6;
  background: #eff6ff;
}

.widget-option.selected {
  border-color: #3b82f6;
  background: #dbeafe;
}

.widget-icon {
  width: 32px;
  height: 32px;
  margin: 0 auto 8px;
  color: #6b7280;
}

.widget-option.selected .widget-icon {
  color: #3b82f6;
}

.widget-name {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: #374151;
}

.dashboard-viewer {
  padding: 0;
}

.viewer-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0;
  margin-bottom: 24px;
  border-bottom: 1px solid #e5e7eb;
  flex-wrap: wrap;
  gap: 16px;
}

.date-range-selector {
  display: flex;
  align-items: center;
  gap: 12px;
}

.date-range-selector label {
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.range-select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  background: white;
  cursor: pointer;
}

.viewer-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.dashboard-grid {
  display: grid;
  gap: 20px;
  margin-bottom: 24px;
}

.dashboard-grid.layout-grid-1 {
  grid-template-columns: 1fr;
}

.dashboard-grid.layout-grid-2 {
  grid-template-columns: repeat(2, 1fr);
}

.dashboard-grid.layout-grid-3 {
  grid-template-columns: repeat(3, 1fr);
}

.dashboard-grid.layout-grid-4 {
  grid-template-columns: repeat(4, 1fr);
}

.widget-container {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.widget-header {
  padding: 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.widget-header h4 {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.widget-body {
  padding: 24px;
}

.chart-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  background: #f9fafb;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  padding: 24px;
}

.placeholder-icon {
  width: 48px;
  height: 48px;
  color: #9ca3af;
  margin-bottom: 12px;
}

.placeholder-text {
  font-size: 14px;
  font-weight: 500;
  color: #6b7280;
  margin-bottom: 4px;
}

.placeholder-note {
  font-size: 12px;
  color: #9ca3af;
  text-align: center;
}

.feature-note {
  margin-top: 24px;
  padding: 16px;
  background: #fffbeb;
  border: 1px solid #fcd34d;
  border-radius: 8px;
}

.feature-note p {
  font-size: 13px;
  color: #92400e;
  margin: 0;
}

@media (max-width: 768px) {
  .dashboards-grid {
    grid-template-columns: 1fr;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .widgets-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .dashboard-grid.layout-grid-2,
  .dashboard-grid.layout-grid-3,
  .dashboard-grid.layout-grid-4 {
    grid-template-columns: 1fr;
  }
}
</style>
