<template>
  <div>
    <div class="mb-6">
      <button @click="$router.back()" class="text-blue-600 hover:text-blue-800 mb-2">← Back</button>
      <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edit' : 'Create' }} Appointment</h1>
    </div>

    <BaseCard>
      <form @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseInput v-model="form.customer_name" label="Customer" required />
          <BaseInput v-model="form.date" label="Date" type="datetime-local" required />
          <BaseSelect v-model="form.status" label="Status" :options="statusOptions" required />
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea v-model="form.notes" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
          </div>
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
import BaseSelect from '@components/common/BaseSelect.vue'

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const isEdit = computed(() => !!route.params.id)

const form = reactive({
  customer_name: '',
  date: '',
  status: 'scheduled',
  notes: ''
})

const statusOptions = [
  { value: 'scheduled', label: 'Scheduled' },
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' }
]

const fetchAppointment = async () => {
  if (!isEdit.value) return
  loading.value = true
  try {
    const response = await api.appointments.get(route.params.id)
    Object.assign(form, response.data)
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    if (isEdit.value) {
      await api.appointments.update(route.params.id, form)
    } else {
      await api.appointments.create(form)
    }
    router.push({ name: 'appointments' })
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchAppointment())
</script>
