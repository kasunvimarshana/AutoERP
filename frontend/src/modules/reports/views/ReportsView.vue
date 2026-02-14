<template>
  <div>
    <h1 class="text-3xl font-bold text-secondary-900 mb-6">{{ $t('reports.title') }}</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <BaseCard 
        v-for="report in reportTypes" 
        :key="report.id"
        class="cursor-pointer hover:shadow-lg transition-shadow"
        @click="selectReport(report)"
      >
        <div class="text-center">
          <div class="text-5xl mb-4">{{ report.icon }}</div>
          <h3 class="text-lg font-semibold text-secondary-900">{{ $t(report.title) }}</h3>
          <p class="text-sm text-secondary-600 mt-2">{{ report.description }}</p>
        </div>
      </BaseCard>
    </div>

    <BaseCard v-if="selectedReport" :title="$t(selectedReport.title)">
      <div class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <BaseInput
            v-model="filters.startDate"
            type="date"
            :label="$t('reports.dateRange') + ' - Start'"
          />
          <BaseInput
            v-model="filters.endDate"
            type="date"
            :label="$t('reports.dateRange') + ' - End'"
          />
        </div>
        <div class="mt-4 flex justify-end space-x-3">
          <BaseButton @click="generateReport" variant="primary">
            {{ $t('reports.generate') }}
          </BaseButton>
          <BaseButton @click="exportReport" variant="secondary">
            {{ $t('common.export') }}
          </BaseButton>
        </div>
      </div>

      <div v-if="reportData" class="mt-6">
        <div class="h-96 flex items-center justify-center bg-secondary-50 rounded-lg">
          <p class="text-secondary-400">Report Chart Placeholder</p>
        </div>

        <div class="mt-6">
          <BaseTable
            :columns="reportColumns"
            :data="reportData"
            :empty-text="$t('common.noData')"
          />
        </div>
      </div>
    </BaseCard>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import BaseCard from '@/components/common/BaseCard.vue'
import BaseInput from '@/components/common/BaseInput.vue'
import BaseButton from '@/components/common/BaseButton.vue'
import BaseTable from '@/components/common/BaseTable.vue'

const reportTypes = [
  {
    id: 'sales',
    icon: '📊',
    title: 'reports.sales',
    description: 'Sales performance and trends'
  },
  {
    id: 'inventory',
    icon: '📦',
    title: 'reports.inventory',
    description: 'Stock levels and movements'
  },
  {
    id: 'financial',
    icon: '💰',
    title: 'reports.financial',
    description: 'Revenue and expenses'
  }
]

const selectedReport = ref(null)
const filters = ref({
  startDate: '',
  endDate: ''
})
const reportData = ref(null)
const reportColumns = ref([
  { key: 'date', label: 'Date', sortable: true },
  { key: 'description', label: 'Description', sortable: true },
  { key: 'amount', label: 'Amount', sortable: true },
  { key: 'status', label: 'Status', sortable: true }
])

const selectReport = (report) => {
  selectedReport.value = report
  reportData.value = null
}

const generateReport = () => {
  reportData.value = [
    { date: '2024-01-01', description: 'Sample Item 1', amount: '$1,234', status: 'Completed' },
    { date: '2024-01-02', description: 'Sample Item 2', amount: '$2,345', status: 'Pending' },
    { date: '2024-01-03', description: 'Sample Item 3', amount: '$3,456', status: 'Completed' }
  ]
}

const exportReport = () => {
  alert('Export functionality would be implemented here')
}
</script>
