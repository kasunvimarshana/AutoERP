<template>
  <div>
    <div class="mb-6 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('vehicles.title') }}</h1>
      <BaseButton @click="$router.push({ name: 'vehicles-create' })">
        {{ $t('vehicles.create') }}
      </BaseButton>
    </div>

    <BaseCard>
      <BaseTable :columns="columns" :data="vehicles" :loading="loading">
        <template #actions="{ row }">
          <div class="flex gap-2">
            <button @click="editVehicle(row.id)" class="text-green-600 hover:text-green-800">Edit</button>
            <button @click="deleteVehicle(row.id)" class="text-red-600 hover:text-red-800">Delete</button>
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
import BaseButton from '@components/common/BaseButton.vue'
import BaseCard from '@components/common/BaseCard.vue'
import BaseTable from '@components/common/BaseTable.vue'

const router = useRouter()
const vehicles = ref([])
const loading = ref(false)

const columns = [
  { key: 'make', label: 'Make' },
  { key: 'model', label: 'Model' },
  { key: 'year', label: 'Year' },
  { key: 'vin', label: 'VIN' },
  { key: 'license_plate', label: 'License Plate' }
]

const fetchVehicles = async () => {
  loading.value = true
  try {
    const response = await api.vehicles.getAll()
    vehicles.value = response.data.data || response.data
  } finally {
    loading.value = false
  }
}

const editVehicle = (id) => router.push({ name: 'vehicles-edit', params: { id } })

const deleteVehicle = async (id) => {
  if (!confirm('Are you sure you want to delete this vehicle? This action cannot be undone.')) return
  try {
    await api.vehicles.delete(id)
    fetchVehicles()
  } catch (error) {
    console.error('Error:', error)
  }
}

onMounted(() => fetchVehicles())
</script>
