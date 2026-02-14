<template>
  <div>
    <div class="flex items-center mb-6">
      <button @click="$router.back()" class="mr-4 text-secondary-600 hover:text-secondary-900">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      <h1 class="text-3xl font-bold text-secondary-900">{{ $t('payment.edit') }}</h1>
    </div>

    <div v-if="store.loading" class="flex justify-center py-12">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <div v-else-if="store.currentItem" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2">
        <BaseCard :title="$t('common.edit')">
          <form @submit.prevent="handleSubmit" class="space-y-4">
            <BaseInput
              v-model="form.name"
              :label="$t('payment.name')"
              required
            />

            <BaseInput
              v-model="form.email"
              type="email"
              :label="$t('payment.email')"
              required
            />

            <div class="flex justify-end space-x-3">
              <BaseButton @click="$router.back()" variant="secondary">
                {{ $t('common.cancel') }}
              </BaseButton>
              <BaseButton type="submit" variant="primary" :loading="loading">
                {{ $t('common.save') }}
              </BaseButton>
            </div>
          </form>
        </BaseCard>
      </div>

      <div>
        <BaseCard title="Information">
          <dl class="space-y-3">
            <div>
              <dt class="text-sm font-medium text-secondary-500">ID</dt>
              <dd class="mt-1 text-sm text-secondary-900">{{ store.currentItem.id }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-secondary-500">Created</dt>
              <dd class="mt-1 text-sm text-secondary-900">{{ store.currentItem.created_at }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-secondary-500">Status</dt>
              <dd class="mt-1">
                <span :class="getStatusClass(store.currentItem.status)">
                  {{ store.currentItem.status }}
                </span>
              </dd>
            </div>
          </dl>
        </BaseCard>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePaymentStore } from '../store/paymentStore'
import BaseCard from '@/components/common/BaseCard.vue'
import BaseInput from '@/components/common/BaseInput.vue'
import BaseButton from '@/components/common/BaseButton.vue'

const route = useRoute()
const router = useRouter()
const store = usePaymentStore()

const form = ref({
  name: '',
  email: ''
})

const loading = ref(false)

const fetchData = async () => {
  try {
    const data = await store.fetchById(route.params.id)
    form.value = { ...data }
  } catch (error) {
    console.error('Error fetching data:', error)
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    await store.update(route.params.id, form.value)
    router.back()
  } catch (error) {
    console.error('Error updating:', error)
  } finally {
    loading.value = false
  }
}

const getStatusClass = (status) => {
  const classes = {
    active: 'px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs',
    inactive: 'px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs',
    pending: 'px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs'
  }
  return classes[status] || classes.pending
}

onMounted(() => {
  fetchData()
})
</script>
