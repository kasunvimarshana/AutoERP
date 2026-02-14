<template>
  <div>
    <div class="mb-6 flex justify-between items-center">
      <h1 class="text-2xl font-bold text-gray-900">{{ $t('contacts.title') }}</h1>
      <BaseButton @click="$router.push({ name: 'contacts-create' })">
        {{ $t('contacts.create') }}
      </BaseButton>
    </div>

    <BaseCard>
      <BaseTable :columns="columns" :data="contacts" :loading="loading">
        <template #actions="{ row }">
          <div class="flex gap-2">
            <button @click="editContact(row.id)" class="text-green-600 hover:text-green-800">
              {{ $t('common.edit') }}
            </button>
            <button @click="deleteContact(row.id)" class="text-red-600 hover:text-red-800">
              {{ $t('common.delete') }}
            </button>
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
const contacts = ref([])
const loading = ref(false)

const columns = [
  { key: 'first_name', label: 'First Name' },
  { key: 'last_name', label: 'Last Name' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
  { key: 'position', label: 'Position' }
]

const fetchContacts = async () => {
  loading.value = true
  try {
    const response = await api.contacts.getAll()
    contacts.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching contacts:', error)
  } finally {
    loading.value = false
  }
}

const editContact = (id) => {
  router.push({ name: 'contacts-edit', params: { id } })
}

const deleteContact = async (id) => {
  if (!confirm('Are you sure you want to delete this contact? This action cannot be undone.')) return
  try {
    await api.contacts.delete(id)
    fetchContacts()
  } catch (error) {
    console.error('Error deleting contact:', error)
  }
}

onMounted(() => {
  fetchContacts()
})
</script>
