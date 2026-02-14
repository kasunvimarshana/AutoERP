<template>
  <div>
    <div class="mb-6 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('appointments.title') }}</h1>
      <BaseButton @click="$router.push({ name: 'appointments-create' })">
        {{ $t('appointments.create') }}
      </BaseButton>
    </div>

    <BaseCard>
      <BaseTable :columns="columns" :data="appointments" :loading="loading">
        <template #cell-date="{ value }">
          {{ formatDate(value, 'YYYY-MM-DD HH:mm') }}
        </template>
        
        <template #cell-status="{ value }">
          <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(value)">
            {{ value }}
          </span>
        </template>
        
        <template #actions="{ row }">
          <div class="flex gap-2">
            <button @click="editAppointment(row.id)" class="text-green-600 hover:text-green-800">Edit</button>
            <button @click="deleteAppointment(row.id)" class="text-red-600 hover:text-red-800">Delete</button>
          </div>
        </template>
      </BaseTable>
    </BaseCard>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/api'
import { formatDate } from '@/utils/formatters'
import BaseButton from '@components/common/BaseButton.vue'
import BaseCard from '@components/common/BaseCard.vue'
import BaseTable from '@components/common/BaseTable.vue'

const router = useRouter()
const appointments = ref([])
const loading = ref(false)

const columns = [
  { key: 'customer_name', label: 'Customer' },
  { key: 'date', label: 'Date & Time' },
  { key: 'status', label: 'Status' },
  { key: 'notes', label: 'Notes' }
]

const fetchAppointments = async () => {
  loading.value = true
  try {
    const response = await api.appointments.getAll()
    appointments.value = response.data.data || response.data
  } finally {
    loading.value = false
  }
}

const editAppointment = (id) => router.push({ name: 'appointments-edit', params: { id } })

const deleteAppointment = async (id) => {
  if (!confirm('Are you sure you want to delete this appointment? This action cannot be undone.')) return
  try {
    await api.appointments.delete(id)
    fetchAppointments()
  } catch (error) {
    console.error('Error:', error)
  }
}

const getStatusClass = (status) => {
  const classes = {
    scheduled: 'bg-blue-100 text-blue-800',
    confirmed: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

onMounted(() => fetchAppointments())
</script>
