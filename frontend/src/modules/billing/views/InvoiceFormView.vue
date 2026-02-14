<template>
  <div>
    <div class="mb-6">
      <button @click="$router.back()" class="text-blue-600 hover:text-blue-800 mb-2">← Back</button>
      <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edit' : 'Create' }} Invoice</h1>
    </div>

    <BaseCard>
      <form @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseInput v-model="form.number" label="Invoice Number" required />
          <BaseInput v-model="form.customer_name" label="Customer" required />
          <BaseInput v-model="form.date" label="Date" type="date" required />
          <BaseInput v-model="form.due_date" label="Due Date" type="date" required />
          <BaseInput v-model="form.amount" label="Amount" type="number" step="0.01" required />
          <BaseSelect v-model="form.status" label="Status" :options="statusOptions" required />
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
  number: '',
  customer_name: '',
  date: new Date().toISOString().split('T')[0],
  due_date: new Date().toISOString().split('T')[0],
  amount: 0,
  status: 'draft'
})

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'sent', label: 'Sent' },
  { value: 'paid', label: 'Paid' },
  { value: 'overdue', label: 'Overdue' },
  { value: 'void', label: 'Void' }
]

const fetchInvoice = async () => {
  if (!isEdit.value) return
  loading.value = true
  try {
    const response = await api.invoices.get(route.params.id)
    Object.assign(form, response.data)
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    if (isEdit.value) {
      await api.invoices.update(route.params.id, form)
    } else {
      await api.invoices.create(form)
    }
    router.push({ name: 'invoices' })
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchInvoice())
</script>
