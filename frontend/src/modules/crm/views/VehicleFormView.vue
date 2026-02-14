<template>
  <div>
    <div class="mb-6">
      <button @click="$router.back()" class="text-blue-600 hover:text-blue-800 mb-2">← Back</button>
      <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edit' : 'Create' }} Vehicle</h1>
    </div>

    <BaseCard>
      <form @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseInput v-model="form.make" label="Make" required />
          <BaseInput v-model="form.model" label="Model" required />
          <BaseInput v-model="form.year" label="Year" type="number" required />
          <BaseInput v-model="form.vin" label="VIN" />
          <BaseInput v-model="form.license_plate" label="License Plate" />
        </div>

        <div class="mt-6 flex gap-2">
          <BaseButton type="submit" :loading="loading">Save</BaseButton>
          <BaseButton variant="outline" @click="$router.back()">Cancel</BaseButton>
        </div>
      </form>
    </BaseCard>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/api'
import BaseButton from '@components/common/BaseButton.vue'
import BaseCard from '@components/common/BaseCard.vue'
import BaseInput from '@components/common/BaseInput.vue'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const isEdit = computed(() => !!route.params.id)

const form = reactive({
  make: '',
  model: '',
  year: new Date().getFullYear(),
  vin: '',
  license_plate: ''
})

const fetchVehicle = async () => {
  if (!isEdit.value) return
  loading.value = true
  try {
    const response = await api.vehicles.get(route.params.id)
    Object.assign(form, response.data)
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    if (isEdit.value) {
      await api.vehicles.update(route.params.id, form)
    } else {
      await api.vehicles.create(form)
    }
    router.push({ name: 'vehicles' })
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchVehicle())
</script>
