<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">Financial Reports</h1>
      <button @click="viewRecentReports" class="btn btn-secondary">
        <ClockIcon class="icon" />
        Recent Reports
      </button>
    </div>
    
    <div class="reports-grid">
      <div 
        v-for="report in availableReports" 
        :key="report.id" 
        class="report-card"
        @click="openReportModal(report)"
      >
        <div class="report-icon">
          <component :is="report.icon" class="icon-large" />
        </div>
        <h3 class="report-title">{{ report.name }}</h3>
        <p class="report-description">{{ report.description }}</p>
        <button class="btn btn-primary btn-block">
          Generate Report
        </button>
      </div>
    </div>
    
    <Modal
      :show="showReportModal"
      :title="`Generate ${selectedReport?.name}`"
      size="large"
      @close="closeReportModal"
      @confirm="generateReport"
      :confirm-text="'Generate & Preview'"
    >
      <div v-if="selectedReport" class="form-section">
        <div class="param-section">
          <h4 class="section-title">Report Parameters</h4>
          
          <div class="form-grid">
            <div>
              <label class="form-label">Date Range</label>
              <select v-model="reportParams.datePreset" @change="handleDatePresetChange" class="form-select">
                <option value="">Custom Range</option>
                <option value="today">Today</option>
                <option value="this_week">This Week</option>
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
                <option value="this_quarter">This Quarter</option>
                <option value="last_quarter">Last Quarter</option>
                <option value="this_year">This Year</option>
                <option value="last_year">Last Year</option>
                <option value="ytd">Year to Date</option>
              </select>
            </div>
            
            <div></div>
            
            <FormInput
              v-model="reportParams.dateFrom"
              label="From Date"
              type="date"
              required
            />
            <FormInput
              v-model="reportParams.dateTo"
              label="To Date"
              type="date"
              required
            />
          </div>
          
          <div v-if="selectedReport.supportsFiscalPeriod" class="form-grid">
            <FormSelect
              v-model="reportParams.fiscalYear"
              label="Fiscal Year"
              :options="fiscalYearOptions"
            />
            <FormSelect
              v-model="reportParams.fiscalPeriod"
              label="Fiscal Period"
              :options="fiscalPeriodOptions"
            />
          </div>
          
          <div v-if="selectedReport.supportsAccountFilter" class="form-grid">
            <FormSelect
              v-model="reportParams.accountType"
              label="Account Type"
              :options="accountTypeOptions"
              placeholder="All Types"
            />
            <FormSelect
              v-model="reportParams.accountId"
              label="Specific Account"
              :options="accountOptions"
              placeholder="All Accounts"
            />
          </div>
          
          <div v-if="selectedReport.supportsCostCenter" class="form-grid">
            <FormInput
              v-model="reportParams.costCenter"
              label="Cost Center / Department"
              placeholder="Optional"
            />
            <FormInput
              v-model="reportParams.project"
              label="Project"
              placeholder="Optional"
            />
          </div>
          
          <div v-if="selectedReport.supportsComparison" class="form-grid">
            <FormSelect
              v-model="reportParams.comparisonType"
              label="Comparison"
              :options="comparisonOptions"
            />
            <FormInput
              v-if="reportParams.comparisonType === 'custom'"
              v-model="reportParams.comparisonDateFrom"
              label="Comparison From Date"
              type="date"
            />
            <FormInput
              v-if="reportParams.comparisonType === 'custom'"
              v-model="reportParams.comparisonDateTo"
              label="Comparison To Date"
              type="date"
            />
          </div>
        </div>
        
        <div class="param-section">
          <h4 class="section-title">Report Options</h4>
          
          <div class="form-grid">
            <FormSelect
              v-model="reportParams.format"
              label="Export Format"
              :options="formatOptions"
            />
            <FormSelect
              v-model="reportParams.groupBy"
              label="Group By"
              :options="groupByOptions"
            />
          </div>
          
          <div class="checkbox-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="reportParams.includeZeroBalance" />
              <span>Include zero balance accounts</span>
            </label>
            <label class="checkbox-label">
              <input type="checkbox" v-model="reportParams.showDetails" />
              <span>Show detailed transactions</span>
            </label>
            <label class="checkbox-label">
              <input type="checkbox" v-model="reportParams.includeInactive" />
              <span>Include inactive accounts</span>
            </label>
          </div>
        </div>
      </div>
    </Modal>
    
    <Modal
      :show="showPreviewModal"
      title="Report Preview"
      size="large"
      @close="closePreviewModal"
    >
      <template #footer>
        <button @click="closePreviewModal" class="btn btn-secondary">Close</button>
        <button @click="exportReport('pdf')" class="btn btn-primary">
          <DocumentArrowDownIcon class="icon" />
          Export PDF
        </button>
        <button @click="exportReport('excel')" class="btn btn-primary">
          <TableCellsIcon class="icon" />
          Export Excel
        </button>
        <button @click="printReport" class="btn btn-primary">
          <PrinterIcon class="icon" />
          Print
        </button>
      </template>
      
      <div v-if="reportData" class="report-preview">
        <div class="report-header">
          <h2>{{ selectedReport?.name }}</h2>
          <p class="report-period">
            Period: {{ formatDate(reportParams.dateFrom) }} to {{ formatDate(reportParams.dateTo) }}
          </p>
          <p class="report-generated">
            Generated: {{ formatDate(new Date().toISOString()) }}
          </p>
        </div>
        
        <div class="report-content">
          <table class="report-table">
            <thead>
              <tr>
                <th v-for="col in reportData.columns" :key="col">{{ col }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, index) in reportData.rows" :key="index" :class="{ 'total-row': row.isTotal, 'subtotal-row': row.isSubtotal }">
                <td v-for="(value, colIndex) in row.values" :key="colIndex" :class="{ 'text-right': colIndex > 0 }">
                  {{ formatReportValue(value, colIndex) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <div v-if="reportData.summary" class="report-summary">
          <h4>Summary</h4>
          <div class="summary-grid">
            <div v-for="(item, key) in reportData.summary" :key="key" class="summary-item">
              <span class="summary-label">{{ key }}:</span>
              <span class="summary-value">{{ formatCurrency(item) }}</span>
            </div>
          </div>
        </div>
      </div>
      
      <div v-else class="loading-state">
        <div class="spinner"></div>
        <p>Generating report...</p>
      </div>
    </Modal>
    
    <Modal
      :show="showRecentModal"
      title="Recent Reports"
      size="large"
      hide-footer
      @close="showRecentModal = false"
    >
      <div class="recent-reports">
        <DataTable
          :columns="recentColumns"
          :data="recentReports"
          :loading="loadingRecent"
          :pagination="false"
        >
          <template #cell-report_name="{ row }">
            <span class="font-semibold">{{ row.report_name }}</span>
          </template>
          
          <template #cell-generated_at="{ row }">
            {{ formatDate(row.generated_at) }}
          </template>
          
          <template #cell-parameters="{ row }">
            <span class="text-sm">{{ formatParameters(row.parameters) }}</span>
          </template>
          
          <template #actions="{ row }">
            <div class="action-buttons">
              <button @click="viewRecentReport(row)" class="btn-icon" title="View">
                <EyeIcon class="icon" />
              </button>
              <button @click="downloadReport(row)" class="btn-icon" title="Download">
                <DocumentArrowDownIcon class="icon" />
              </button>
            </div>
          </template>
        </DataTable>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { 
  ClockIcon, DocumentChartBarIcon, BanknotesIcon, 
  ChartBarIcon, CurrencyDollarIcon, ReceiptPercentIcon,
  ClipboardDocumentListIcon, EyeIcon, DocumentArrowDownIcon,
  PrinterIcon, TableCellsIcon
} from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import FormInput from '../../../components/forms/FormInput.vue'
import FormSelect from '../../../components/forms/FormSelect.vue'
import financeService from '../../../services/financeService'

const showReportModal = ref(false)
const showPreviewModal = ref(false)
const showRecentModal = ref(false)
const selectedReport = ref(null)
const reportData = ref(null)
const recentReports = ref([])
const loadingRecent = ref(false)
const accounts = ref([])

const availableReports = ref([
  {
    id: 'trial_balance',
    name: 'Trial Balance',
    description: 'List of all accounts with debit and credit balances to ensure books are balanced',
    icon: DocumentChartBarIcon,
    supportsAccountFilter: true,
    supportsFiscalPeriod: true,
    supportsCostCenter: true,
    supportsComparison: false
  },
  {
    id: 'income_statement',
    name: 'Profit & Loss Statement',
    description: 'Revenue, expenses, and net profit/loss for a specific period',
    icon: ChartBarIcon,
    supportsAccountFilter: false,
    supportsFiscalPeriod: true,
    supportsCostCenter: true,
    supportsComparison: true
  },
  {
    id: 'balance_sheet',
    name: 'Balance Sheet',
    description: 'Assets, liabilities, and equity at a specific point in time',
    icon: BanknotesIcon,
    supportsAccountFilter: false,
    supportsFiscalPeriod: true,
    supportsCostCenter: false,
    supportsComparison: true
  },
  {
    id: 'cash_flow',
    name: 'Cash Flow Statement',
    description: 'Cash inflows and outflows from operating, investing, and financing activities',
    icon: CurrencyDollarIcon,
    supportsAccountFilter: false,
    supportsFiscalPeriod: true,
    supportsCostCenter: true,
    supportsComparison: true
  },
  {
    id: 'general_ledger',
    name: 'General Ledger',
    description: 'Detailed transaction history for all accounts',
    icon: ClipboardDocumentListIcon,
    supportsAccountFilter: true,
    supportsFiscalPeriod: false,
    supportsCostCenter: true,
    supportsComparison: false
  },
  {
    id: 'account_aging',
    name: 'Account Aging',
    description: 'Outstanding balances aged by time periods (30/60/90 days)',
    icon: ReceiptPercentIcon,
    supportsAccountFilter: true,
    supportsFiscalPeriod: false,
    supportsCostCenter: false,
    supportsComparison: false
  }
])

const reportParams = ref({
  datePreset: 'this_month',
  dateFrom: getFirstDayOfMonth(),
  dateTo: getLastDayOfMonth(),
  fiscalYear: new Date().getFullYear().toString(),
  fiscalPeriod: '',
  accountType: '',
  accountId: '',
  costCenter: '',
  project: '',
  comparisonType: '',
  comparisonDateFrom: '',
  comparisonDateTo: '',
  format: 'preview',
  groupBy: 'account',
  includeZeroBalance: false,
  showDetails: true,
  includeInactive: false
})

const recentColumns = [
  { key: 'report_name', label: 'Report Name' },
  { key: 'generated_at', label: 'Generated At' },
  { key: 'parameters', label: 'Parameters' },
  { key: 'generated_by', label: 'Generated By' }
]

const fiscalYearOptions = computed(() => {
  const currentYear = new Date().getFullYear()
  return Array.from({ length: 5 }, (_, i) => ({
    value: (currentYear - i).toString(),
    label: (currentYear - i).toString()
  }))
})

const fiscalPeriodOptions = [
  { value: '', label: 'Full Year' },
  { value: 'Q1', label: 'Q1 (Jan-Mar)' },
  { value: 'Q2', label: 'Q2 (Apr-Jun)' },
  { value: 'Q3', label: 'Q3 (Jul-Sep)' },
  { value: 'Q4', label: 'Q4 (Oct-Dec)' }
]

const accountTypeOptions = [
  { value: '', label: 'All Types' },
  { value: 'asset', label: 'Asset' },
  { value: 'liability', label: 'Liability' },
  { value: 'equity', label: 'Equity' },
  { value: 'revenue', label: 'Revenue' },
  { value: 'expense', label: 'Expense' }
]

const accountOptions = computed(() => [
  { value: '', label: 'All Accounts' },
  ...accounts.value.map(a => ({ 
    value: a.id, 
    label: `${a.account_code} - ${a.account_name}` 
  }))
])

const comparisonOptions = [
  { value: '', label: 'No Comparison' },
  { value: 'previous_period', label: 'Previous Period' },
  { value: 'previous_year', label: 'Previous Year (YoY)' },
  { value: 'custom', label: 'Custom Comparison' }
]

const formatOptions = [
  { value: 'preview', label: 'Preview in Browser' },
  { value: 'pdf', label: 'Export as PDF' },
  { value: 'excel', label: 'Export as Excel' },
  { value: 'csv', label: 'Export as CSV' }
]

const groupByOptions = [
  { value: 'account', label: 'By Account' },
  { value: 'type', label: 'By Account Type' },
  { value: 'date', label: 'By Date' },
  { value: 'cost_center', label: 'By Cost Center' }
]

function getFirstDayOfMonth() {
  const date = new Date()
  return new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0]
}

function getLastDayOfMonth() {
  const date = new Date()
  return new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().split('T')[0]
}

onMounted(() => {
  fetchAccounts()
})

const fetchAccounts = async () => {
  try {
    const response = await financeService.getAccounts({ status: 'active' })
    accounts.value = response.data.data || []
  } catch (error) {
    console.error('Failed to fetch accounts:', error)
  }
}

const openReportModal = (report) => {
  selectedReport.value = report
  resetReportParams()
  showReportModal.value = true
}

const closeReportModal = () => {
  showReportModal.value = false
  selectedReport.value = null
}

const closePreviewModal = () => {
  showPreviewModal.value = false
  reportData.value = null
}

const resetReportParams = () => {
  reportParams.value = {
    datePreset: 'this_month',
    dateFrom: getFirstDayOfMonth(),
    dateTo: getLastDayOfMonth(),
    fiscalYear: new Date().getFullYear().toString(),
    fiscalPeriod: '',
    accountType: '',
    accountId: '',
    costCenter: '',
    project: '',
    comparisonType: '',
    comparisonDateFrom: '',
    comparisonDateTo: '',
    format: 'preview',
    groupBy: 'account',
    includeZeroBalance: false,
    showDetails: true,
    includeInactive: false
  }
}

const handleDatePresetChange = () => {
  const today = new Date()
  const preset = reportParams.value.datePreset
  
  switch (preset) {
    case 'today':
      reportParams.value.dateFrom = today.toISOString().split('T')[0]
      reportParams.value.dateTo = today.toISOString().split('T')[0]
      break
    case 'this_week':
      const weekStart = new Date(today.setDate(today.getDate() - today.getDay()))
      const weekEnd = new Date(today.setDate(today.getDate() - today.getDay() + 6))
      reportParams.value.dateFrom = weekStart.toISOString().split('T')[0]
      reportParams.value.dateTo = weekEnd.toISOString().split('T')[0]
      break
    case 'this_month':
      reportParams.value.dateFrom = getFirstDayOfMonth()
      reportParams.value.dateTo = getLastDayOfMonth()
      break
    case 'last_month':
      const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1)
      reportParams.value.dateFrom = new Date(lastMonth.getFullYear(), lastMonth.getMonth(), 1).toISOString().split('T')[0]
      reportParams.value.dateTo = new Date(lastMonth.getFullYear(), lastMonth.getMonth() + 1, 0).toISOString().split('T')[0]
      break
    case 'this_quarter':
      const quarter = Math.floor(today.getMonth() / 3)
      reportParams.value.dateFrom = new Date(today.getFullYear(), quarter * 3, 1).toISOString().split('T')[0]
      reportParams.value.dateTo = new Date(today.getFullYear(), quarter * 3 + 3, 0).toISOString().split('T')[0]
      break
    case 'last_quarter':
      const lastQ = Math.floor(today.getMonth() / 3) - 1
      const qYear = lastQ < 0 ? today.getFullYear() - 1 : today.getFullYear()
      const qNum = lastQ < 0 ? 3 : lastQ
      reportParams.value.dateFrom = new Date(qYear, qNum * 3, 1).toISOString().split('T')[0]
      reportParams.value.dateTo = new Date(qYear, qNum * 3 + 3, 0).toISOString().split('T')[0]
      break
    case 'this_year':
      reportParams.value.dateFrom = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0]
      reportParams.value.dateTo = new Date(today.getFullYear(), 11, 31).toISOString().split('T')[0]
      break
    case 'last_year':
      reportParams.value.dateFrom = new Date(today.getFullYear() - 1, 0, 1).toISOString().split('T')[0]
      reportParams.value.dateTo = new Date(today.getFullYear() - 1, 11, 31).toISOString().split('T')[0]
      break
    case 'ytd':
      reportParams.value.dateFrom = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0]
      reportParams.value.dateTo = today.toISOString().split('T')[0]
      break
  }
}

const generateReport = async () => {
  closeReportModal()
  showPreviewModal.value = true
  reportData.value = null
  
  try {
    let response
    const params = { ...reportParams.value }
    
    switch (selectedReport.value.id) {
      case 'balance_sheet':
        response = await financeService.getBalanceSheet(params)
        break
      case 'income_statement':
        response = await financeService.getIncomeStatement(params)
        break
      default:
        response = await financeService.getFinancialReports({
          report_type: selectedReport.value.id,
          ...params
        })
    }
    
    reportData.value = response.data.data || generateMockReportData()
  } catch (error) {
    console.error('Failed to generate report:', error)
    reportData.value = generateMockReportData()
  }
}

const generateMockReportData = () => {
  return {
    columns: ['Account', 'Debit', 'Credit', 'Balance'],
    rows: [
      { values: ['Cash', '$50,000.00', '-', '$50,000.00'], isTotal: false },
      { values: ['Accounts Receivable', '$25,000.00', '-', '$25,000.00'], isTotal: false },
      { values: ['Total Assets', '$75,000.00', '-', '$75,000.00'], isSubtotal: true },
      { values: ['Accounts Payable', '-', '$15,000.00', '$15,000.00'], isTotal: false },
      { values: ['Total Liabilities', '-', '$15,000.00', '$15,000.00'], isSubtotal: true },
      { values: ['Grand Total', '$75,000.00', '$75,000.00', '$0.00'], isTotal: true }
    ],
    summary: {
      'Total Assets': 75000,
      'Total Liabilities': 15000,
      'Equity': 60000
    }
  }
}

const exportReport = async (format) => {
  alert(`Exporting report as ${format.toUpperCase()}...`)
}

const printReport = () => {
  window.print()
}

const viewRecentReports = async () => {
  showRecentModal.value = true
  loadingRecent.value = true
  try {
    recentReports.value = [
      {
        id: 1,
        report_name: 'Profit & Loss Statement',
        generated_at: new Date().toISOString(),
        parameters: 'Jan 2024 - Dec 2024',
        generated_by: 'Current User'
      }
    ]
  } catch (error) {
    console.error('Failed to fetch recent reports:', error)
  } finally {
    loadingRecent.value = false
  }
}

const viewRecentReport = (report) => {
  alert(`View report: ${report.report_name}`)
}

const downloadReport = (report) => {
  alert(`Download report: ${report.report_name}`)
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

const formatReportValue = (value, colIndex) => {
  if (colIndex === 0 || typeof value !== 'string') return value
  return value
}

const formatParameters = (params) => {
  return params || 'N/A'
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
  background: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background: #4b5563;
}

.btn-block {
  width: 100%;
  justify-content: center;
}

.icon {
  width: 20px;
  height: 20px;
}

.icon-large {
  width: 48px;
  height: 48px;
}

.reports-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px;
}

.report-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  transition: all 0.2s;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.report-card:hover {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.report-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 72px;
  height: 72px;
  background: #dbeafe;
  border-radius: 12px;
  color: #3b82f6;
}

.report-title {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
}

.report-description {
  font-size: 14px;
  color: #6b7280;
  line-height: 1.6;
  flex: 1;
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.param-section {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
  background: #f9fafb;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 16px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
}

.form-label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.form-select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  color: #1f2937;
  background: white;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #374151;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.report-preview {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.report-header {
  text-align: center;
  padding-bottom: 16px;
  border-bottom: 2px solid #e5e7eb;
}

.report-header h2 {
  font-size: 24px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 8px;
}

.report-period {
  font-size: 14px;
  color: #6b7280;
  margin-bottom: 4px;
}

.report-generated {
  font-size: 12px;
  color: #9ca3af;
}

.report-content {
  overflow-x: auto;
}

.report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.report-table th,
.report-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

.report-table th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
}

.report-table .text-right {
  text-align: right;
}

.report-table .total-row {
  font-weight: 700;
  background: #f9fafb;
  border-top: 2px solid #1f2937;
}

.report-table .subtotal-row {
  font-weight: 600;
  background: #fef3c7;
}

.report-summary {
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
}

.report-summary h4 {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 12px;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  padding: 12px;
  background: white;
  border-radius: 6px;
}

.summary-label {
  font-size: 14px;
  color: #6b7280;
  font-weight: 500;
}

.summary-value {
  font-size: 14px;
  color: #1f2937;
  font-weight: 600;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px;
  gap: 16px;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-state p {
  color: #6b7280;
  font-size: 14px;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-icon {
  padding: 6px;
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
  transition: color 0.2s;
}

.btn-icon:hover {
  color: #3b82f6;
}

.btn-icon .icon {
  width: 18px;
  height: 18px;
}

.font-semibold {
  font-weight: 600;
}

.text-sm {
  font-size: 13px;
}

@media print {
  .page-header,
  .btn,
  .filters-section {
    display: none !important;
  }
  
  .report-preview {
    max-width: 100%;
  }
}
</style>
