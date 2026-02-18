<template>
  <div class="bulk-operations">
    <div class="operations-header">
      <h3 class="title">
        <ArrowsRightLeftIcon class="icon" />
        Bulk Operations
      </h3>
      <button @click="closePanel" class="btn-close" aria-label="Close">
        <XMarkIcon class="icon" />
      </button>
    </div>
    
    <div class="operations-tabs">
      <button
        :class="['tab', { active: activeTab === 'import' }]"
        @click="activeTab = 'import'"
      >
        <ArrowUpTrayIcon class="icon" />
        Import
      </button>
      <button
        :class="['tab', { active: activeTab === 'export' }]"
        @click="activeTab = 'export'"
      >
        <ArrowDownTrayIcon class="icon" />
        Export
      </button>
    </div>
    
    <!-- Import Tab -->
    <div v-if="activeTab === 'import'" class="tab-content">
      <div class="section">
        <h4 class="section-title">Import Data</h4>
        <p class="section-description">
          Upload a CSV or Excel file to import {{ entityLabel }}
        </p>
        
        <!-- File Upload -->
        <div class="upload-area">
          <input
            ref="fileInput"
            type="file"
            accept=".csv,.xlsx,.xls"
            @change="handleFileSelect"
            class="file-input"
            :disabled="importing"
          />
          
          <div v-if="!selectedFile" class="upload-prompt" @click="triggerFileInput">
            <DocumentArrowUpIcon class="upload-icon" />
            <p class="upload-text">
              Click to select file or drag and drop
            </p>
            <p class="upload-hint">
              CSV or Excel (.xlsx, .xls) files only
            </p>
          </div>
          
          <div v-else class="file-selected">
            <DocumentTextIcon class="file-icon" />
            <div class="file-info">
              <p class="file-name">{{ selectedFile.name }}</p>
              <p class="file-size">{{ formatFileSize(selectedFile.size) }}</p>
            </div>
            <button @click="clearFile" class="btn-remove" :disabled="importing">
              <XMarkIcon class="icon" />
            </button>
          </div>
        </div>
        
        <!-- Import Options -->
        <div class="import-options">
          <label class="option">
            <input
              v-model="importOptions.skipFirstRow"
              type="checkbox"
              :disabled="importing"
            />
            <span>Skip first row (header row)</span>
          </label>
          
          <label class="option">
            <input
              v-model="importOptions.updateExisting"
              type="checkbox"
              :disabled="importing"
            />
            <span>Update existing records</span>
          </label>
          
          <label class="option">
            <input
              v-model="importOptions.validateOnly"
              type="checkbox"
              :disabled="importing"
            />
            <span>Validate only (don't import)</span>
          </label>
        </div>
        
        <!-- Import Button -->
        <button
          @click="performImport"
          :disabled="!selectedFile || importing"
          class="btn btn-primary btn-full"
        >
          <ArrowPathIcon v-if="importing" class="icon animate-spin" />
          <template v-else>
            <ArrowUpTrayIcon class="icon" />
            {{ importOptions.validateOnly ? 'Validate File' : 'Import Data' }}
          </template>
        </button>
        
        <!-- Import Progress -->
        <div v-if="importProgress" class="progress-section">
          <div class="progress-header">
            <span>{{ importProgress.status }}</span>
            <span>{{ importProgress.processed }} / {{ importProgress.total }}</span>
          </div>
          <div class="progress-bar">
            <div
              class="progress-fill"
              :style="{ width: `${importProgress.percentage}%` }"
            />
          </div>
        </div>
        
        <!-- Import Results -->
        <div v-if="importResults" class="results-section">
          <div :class="['result-card', importResults.success ? 'success' : 'error']">
            <CheckCircleIcon v-if="importResults.success" class="result-icon" />
            <XCircleIcon v-else class="result-icon" />
            <div>
              <p class="result-title">
                {{ importResults.success ? 'Import Successful' : 'Import Failed' }}
              </p>
              <p class="result-text">
                {{ importResults.message }}
              </p>
              <div v-if="importResults.stats" class="result-stats">
                <span>✓ {{ importResults.stats.created }} created</span>
                <span v-if="importResults.stats.updated">
                  ↻ {{ importResults.stats.updated }} updated
                </span>
                <span v-if="importResults.stats.failed" class="text-red-600">
                  ✗ {{ importResults.stats.failed }} failed
                </span>
              </div>
            </div>
          </div>
          
          <!-- Errors -->
          <div v-if="importResults.errors?.length" class="errors-list">
            <p class="errors-title">Errors:</p>
            <ul>
              <li v-for="(error, index) in importResults.errors.slice(0, 10)" :key="index">
                Row {{ error.row }}: {{ error.message }}
              </li>
            </ul>
            <p v-if="importResults.errors.length > 10" class="errors-more">
              And {{ importResults.errors.length - 10 }} more errors...
            </p>
          </div>
        </div>
        
        <!-- Download Template -->
        <div class="template-section">
          <button @click="downloadTemplate" class="btn btn-outline btn-full">
            <DocumentArrowDownIcon class="icon" />
            Download Import Template
          </button>
        </div>
      </div>
    </div>
    
    <!-- Export Tab -->
    <div v-else class="tab-content">
      <div class="section">
        <h4 class="section-title">Export Data</h4>
        <p class="section-description">
          Download {{ entityLabel }} in various formats
        </p>
        
        <!-- Export Format -->
        <div class="form-group">
          <label class="form-label">Format</label>
          <select v-model="exportOptions.format" class="form-select">
            <option value="csv">CSV</option>
            <option value="xlsx">Excel (.xlsx)</option>
            <option value="json">JSON</option>
          </select>
        </div>
        
        <!-- Export Options -->
        <div class="export-options">
          <label class="option">
            <input
              v-model="exportOptions.includeHeaders"
              type="checkbox"
            />
            <span>Include column headers</span>
          </label>
          
          <label class="option">
            <input
              v-model="exportOptions.selectedOnly"
              type="checkbox"
              :disabled="!hasSelection"
            />
            <span>Export selected items only ({{ selectedCount }})</span>
          </label>
          
          <label class="option">
            <input
              v-model="exportOptions.allFields"
              type="checkbox"
            />
            <span>Include all fields (including hidden)</span>
          </label>
        </div>
        
        <!-- Export Button -->
        <button
          @click="performExport"
          :disabled="exporting"
          class="btn btn-primary btn-full"
        >
          <ArrowPathIcon v-if="exporting" class="icon animate-spin" />
          <template v-else>
            <ArrowDownTrayIcon class="icon" />
            Export Data
          </template>
        </button>
        
        <!-- Export Success -->
        <div v-if="exportSuccess" class="result-card success">
          <CheckCircleIcon class="result-icon" />
          <div>
            <p class="result-title">Export Successful</p>
            <p class="result-text">
              {{ exportSuccess.count }} records exported successfully
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  ArrowsRightLeftIcon,
  ArrowUpTrayIcon,
  ArrowDownTrayIcon,
  ArrowPathIcon,
  XMarkIcon,
  DocumentArrowUpIcon,
  DocumentArrowDownIcon,
  DocumentTextIcon,
  CheckCircleIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  entityName: {
    type: String,
    required: true
  },
  entityLabel: {
    type: String,
    required: true
  },
  apiEndpoint: {
    type: String,
    required: true
  },
  selectedItems: {
    type: Array,
    default: () => []
  },
  columns: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'import-complete', 'export-complete'])

// State
const activeTab = ref('import')
const fileInput = ref(null)
const selectedFile = ref(null)
const importing = ref(false)
const exporting = ref(false)
const importProgress = ref(null)
const importResults = ref(null)
const exportSuccess = ref(null)

// Import Options
const importOptions = ref({
  skipFirstRow: true,
  updateExisting: false,
  validateOnly: false
})

// Export Options
const exportOptions = ref({
  format: 'csv',
  includeHeaders: true,
  selectedOnly: false,
  allFields: false
})

// Computed
const hasSelection = computed(() => props.selectedItems.length > 0)
const selectedCount = computed(() => props.selectedItems.length)

// Methods
const closePanel = () => {
  emit('close')
}

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    selectedFile.value = file
    importResults.value = null
    importProgress.value = null
  }
}

const clearFile = () => {
  selectedFile.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
  importResults.value = null
  importProgress.value = null
}

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

const performImport = async () => {
  if (!selectedFile.value) return
  
  importing.value = true
  importProgress.value = {
    status: 'Processing...',
    processed: 0,
    total: 0,
    percentage: 0
  }
  
  try {
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    formData.append('skip_first_row', importOptions.value.skipFirstRow)
    formData.append('update_existing', importOptions.value.updateExisting)
    formData.append('validate_only', importOptions.value.validateOnly)
    
    // Simulated import - replace with actual API call
    const response = await fetch(`${props.apiEndpoint}/import`, {
      method: 'POST',
      body: formData
    })
    
    const result = await response.json()
    
    importResults.value = result
    
    if (result.success) {
      emit('import-complete', result)
      
      // Clear file after 3 seconds
      setTimeout(() => {
        clearFile()
      }, 3000)
    }
  } catch (error) {
    importResults.value = {
      success: false,
      message: error.message || 'Import failed'
    }
  } finally {
    importing.value = false
    importProgress.value = null
  }
}

const performExport = async () => {
  exporting.value = true
  exportSuccess.value = null
  
  try {
    const params = new URLSearchParams({
      format: exportOptions.value.format,
      include_headers: exportOptions.value.includeHeaders,
      all_fields: exportOptions.value.allFields
    })
    
    if (exportOptions.value.selectedOnly && hasSelection.value) {
      params.append('ids', props.selectedItems.map(item => item.id).join(','))
    }
    
    const response = await fetch(`${props.apiEndpoint}/export?${params}`)
    const blob = await response.blob()
    
    // Download file
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${props.entityName}-export-${Date.now()}.${exportOptions.value.format}`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
    exportSuccess.value = {
      count: exportOptions.value.selectedOnly ? selectedCount.value : 'All'
    }
    
    emit('export-complete', { format: exportOptions.value.format })
    
    // Clear success message after 3 seconds
    setTimeout(() => {
      exportSuccess.value = null
    }, 3000)
  } catch (error) {
    console.error('Export failed:', error)
    alert('Export failed: ' + error.message)
  } finally {
    exporting.value = false
  }
}

const downloadTemplate = () => {
  // Generate CSV template with column headers
  const headers = props.columns.map(col => col.label || col.key).join(',')
  const blob = new Blob([headers + '\n'], { type: 'text/csv' })
  
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${props.entityName}-import-template.csv`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}
</script>

<style scoped>
.bulk-operations {
  @apply w-full max-w-2xl bg-white rounded-lg shadow-lg;
}

.operations-header {
  @apply flex items-center justify-between p-6 border-b border-gray-200;
}

.title {
  @apply flex items-center text-xl font-semibold text-gray-900;
}

.title .icon {
  @apply w-6 h-6 mr-2 text-blue-600;
}

.btn-close {
  @apply p-2 rounded-lg hover:bg-gray-100 transition-colors;
}

.btn-close .icon {
  @apply w-5 h-5 text-gray-500;
}

.operations-tabs {
  @apply flex border-b border-gray-200;
}

.tab {
  @apply flex-1 flex items-center justify-center px-6 py-4 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-gray-900 hover:border-gray-300 transition-colors;
}

.tab.active {
  @apply text-blue-600 border-blue-600;
}

.tab .icon {
  @apply w-5 h-5 mr-2;
}

.tab-content {
  @apply p-6;
}

.section {
  @apply space-y-6;
}

.section-title {
  @apply text-lg font-semibold text-gray-900;
}

.section-description {
  @apply text-sm text-gray-600;
}

.upload-area {
  @apply relative;
}

.file-input {
  @apply absolute inset-0 w-full h-full opacity-0 cursor-pointer;
}

.upload-prompt {
  @apply flex flex-col items-center justify-center p-8 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-colors;
}

.upload-icon {
  @apply w-12 h-12 text-gray-400 mb-4;
}

.upload-text {
  @apply text-sm font-medium text-gray-700 mb-1;
}

.upload-hint {
  @apply text-xs text-gray-500;
}

.file-selected {
  @apply flex items-center p-4 bg-blue-50 border border-blue-200 rounded-lg;
}

.file-icon {
  @apply w-10 h-10 text-blue-600 mr-4;
}

.file-info {
  @apply flex-1;
}

.file-name {
  @apply text-sm font-medium text-gray-900;
}

.file-size {
  @apply text-xs text-gray-600;
}

.btn-remove {
  @apply p-2 rounded-lg hover:bg-blue-100 transition-colors;
}

.btn-remove .icon {
  @apply w-5 h-5 text-gray-600;
}

.import-options,
.export-options {
  @apply space-y-3;
}

.option {
  @apply flex items-center cursor-pointer;
}

.option input[type="checkbox"] {
  @apply w-4 h-4 text-blue-600 rounded mr-2;
}

.option span {
  @apply text-sm text-gray-700;
}

.btn {
  @apply flex items-center justify-center px-4 py-2 rounded-lg font-medium transition-colors;
}

.btn .icon {
  @apply w-5 h-5 mr-2;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.btn-outline {
  @apply bg-white text-gray-700 border border-gray-300 hover:bg-gray-50;
}

.btn-full {
  @apply w-full;
}

.btn:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.progress-section {
  @apply space-y-2;
}

.progress-header {
  @apply flex justify-between text-sm text-gray-600;
}

.progress-bar {
  @apply w-full h-2 bg-gray-200 rounded-full overflow-hidden;
}

.progress-fill {
  @apply h-full bg-blue-600 transition-all duration-300;
}

.results-section {
  @apply space-y-4;
}

.result-card {
  @apply flex items-start p-4 rounded-lg;
}

.result-card.success {
  @apply bg-green-50 border border-green-200;
}

.result-card.error {
  @apply bg-red-50 border border-red-200;
}

.result-icon {
  @apply w-6 h-6 mr-3 flex-shrink-0;
}

.result-card.success .result-icon {
  @apply text-green-600;
}

.result-card.error .result-icon {
  @apply text-red-600;
}

.result-title {
  @apply font-semibold text-gray-900 mb-1;
}

.result-text {
  @apply text-sm text-gray-700;
}

.result-stats {
  @apply flex gap-4 mt-2 text-xs text-gray-600;
}

.errors-list {
  @apply p-4 bg-red-50 border border-red-200 rounded-lg;
}

.errors-title {
  @apply font-semibold text-red-900 mb-2;
}

.errors-list ul {
  @apply space-y-1;
}

.errors-list li {
  @apply text-sm text-red-700;
}

.errors-more {
  @apply text-sm text-red-600 mt-2;
}

.template-section {
  @apply pt-6 border-t border-gray-200;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply block text-sm font-medium text-gray-700;
}

.form-select {
  @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
