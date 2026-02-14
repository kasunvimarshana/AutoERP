<template>
  <div>
    <div class="mb-6">
      <button @click="$router.back()" class="text-blue-600 hover:text-blue-800 mb-2">← Back</button>
      <h1 class="text-2xl font-bold text-gray-900">{{ isEdit ? 'Edit' : 'Create' }} Product</h1>
    </div>

    <BaseCard>
      <form @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseInput v-model="form.name" label="Name" required />
          <BaseInput v-model="form.sku" label="SKU" required />
          <BaseInput v-model="form.category" label="Category" />
          <BaseInput v-model="form.price" label="Price" type="number" step="0.01" required />
          <BaseInput v-model="form.cost" label="Cost" type="number" step="0.01" />
          <BaseInput v-model="form.stock" label="Stock" type="number" />
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="form.description" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
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

const router = useRouter()
const route = useRoute()
const loading = ref(false)
const isEdit = computed(() => !!route.params.id)

const form = reactive({
  name: '',
  sku: '',
  description: '',
  category: '',
  price: 0,
  cost: 0,
  stock: 0
})

const fetchProduct = async () => {
  if (!isEdit.value) return
  loading.value = true
  try {
    const response = await api.products.get(route.params.id)
    Object.assign(form, response.data)
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    if (isEdit.value) {
      await api.products.update(route.params.id, form)
    } else {
      await api.products.create(form)
    }
    router.push({ name: 'products' })
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchProduct())
</script>
