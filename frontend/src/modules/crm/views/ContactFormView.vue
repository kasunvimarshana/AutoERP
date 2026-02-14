<template>
  <div>
    <div class="mb-6">
      <button @click="$router.back()" class="text-blue-600 hover:text-blue-800 mb-2">
        ← {{ $t('common.back') }}
      </button>
      <h1 class="text-2xl font-bold text-gray-900">
        {{ isEdit ? $t('contacts.edit') : $t('contacts.create') }}
      </h1>
    </div>

    <BaseCard>
      <form @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <BaseInput v-model="form.first_name" :label="$t('contacts.firstName')" required />
          <BaseInput v-model="form.last_name" :label="$t('contacts.lastName')" required />
          <BaseInput v-model="form.email" :label="$t('contacts.email')" type="email" required />
          <BaseInput v-model="form.phone" :label="$t('contacts.phone')" />
          <BaseInput v-model="form.position" :label="$t('contacts.position')" />
        </div>

        <div class="mt-6 flex gap-2">
          <BaseButton type="submit" :loading="loading">{{ $t('common.save') }}</BaseButton>
          <BaseButton variant="outline" @click="$router.back()">{{ $t('common.cancel') }}</BaseButton>
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
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  position: ''
})

const fetchContact = async () => {
  if (!isEdit.value) return
  loading.value = true
  try {
    const response = await api.contacts.get(route.params.id)
    Object.assign(form, response.data)
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loading.value = false
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    if (isEdit.value) {
      await api.contacts.update(route.params.id, form)
    } else {
      await api.contacts.create(form)
    }
    router.push({ name: 'contacts' })
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchContact()
})
</script>
