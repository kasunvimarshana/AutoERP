<template>
  <div>
    <div class="mb-6">
      <button @click="$router.back()" class="text-blue-600 hover:text-blue-800 mb-2">
        ← {{ $t('common.back') }}
      </button>
      <h1 class="text-2xl font-bold text-gray-900">
        {{ isEdit ? $t('customers.edit') : $t('customers.create') }}
      </h1>
    </div>

    <BaseCard>
      <form @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseInput
            v-model="form.name"
            :label="$t('customers.name')"
            required
          />
          
          <BaseInput
            v-model="form.email"
            :label="$t('customers.email')"
            type="email"
            required
          />
          
          <BaseInput
            v-model="form.phone"
            :label="$t('customers.phone')"
          />
          
          <BaseSelect
            v-model="form.type"
            :label="$t('customers.type')"
            :options="typeOptions"
            required
          />
          
          <BaseSelect
            v-model="form.status"
            :label="$t('customers.status')"
            :options="statusOptions"
            required
          />
          
          <div class="md:col-span-2">
            <BaseInput
              v-model="form.address"
              :label="$t('customers.address')"
            />
          </div>
        </div>

        <div class="mt-6 flex gap-2">
          <BaseButton type="submit" :loading="loading">
            {{ $t('common.save') }}
          </BaseButton>
          <BaseButton variant="outline" @click="$router.back()">
            {{ $t('common.cancel') }}
          </BaseButton>
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
  name: '',
  email: '',
  phone: '',
  type: 'individual',
  status: 'active',
  address: ''
})

const typeOptions = [
  { value: 'individual', label: 'Individual' },
  { value: 'business', label: 'Business' }
]

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
]

const fetchCustomer = async () => {
  if (!isEdit.value) return
  
  loading.value = true
  try {
    const response = await api.customers.get(route.params.id)
    Object.assign(form, response.data)
  } catch (error) {
    console.error('Error fetching customer:', error)
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    if (isEdit.value) {
      await api.customers.update(route.params.id, form)
    } else {
      await api.customers.create(form)
    }
    router.push({ name: 'customers' })
  } catch (error) {
    console.error('Error saving customer:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCustomer()
})
</script>
