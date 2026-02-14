<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-secondary-900">{{ $t('product.title') }}</h1>
      <BaseButton @click="showCreateModal = true" variant="primary">
        <span class="mr-2">+</span>
        {{ $t('product.create') }}
      </BaseButton>
    </div>

    <BaseCard>
      <div class="mb-4 flex gap-4">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="$t('common.search')"
          class="input flex-1"
        />
        <BaseButton @click="fetchData" variant="secondary">
          {{ $t('common.search') }}
        </BaseButton>
      </div>

      <BaseTable
        :columns="columns"
        :data="store.items"
        :loading="store.loading"
        :actions="true"
        :empty-text="$t('common.noData')"
      >
        <template #cell-status="{ value }">
          <span :class="getStatusClass(value)">
            {{ value }}
          </span>
        </template>

        <template #actions="{ row }">
          <div class="flex space-x-2">
            <button
              @click="viewDetail(row.id)"
              class="text-primary-600 hover:text-primary-700"
            >
              {{ $t('common.edit') }}
            </button>
            <button
              @click="deleteItem(row.id)"
              class="text-red-600 hover:text-red-700"
            >
              {{ $t('common.delete') }}
            </button>
          </div>
        </template>
      </BaseTable>
    </BaseCard>

    <BaseModal v-model="showCreateModal" :title="$t('product.create')">
      <p class="text-secondary-600">Create form placeholder</p>
      <template #footer>
        <BaseButton @click="showCreateModal = false" variant="secondary">
          {{ $t('common.cancel') }}
        </BaseButton>
        <BaseButton @click="handleCreate" variant="primary">
          {{ $t('common.save') }}
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useProductStore } from '../store/productStore'
import BaseCard from '@/components/common/BaseCard.vue'
import BaseButton from '@/components/common/BaseButton.vue'
import BaseTable from '@/components/common/BaseTable.vue'
import BaseModal from '@/components/common/BaseModal.vue'

const router = useRouter()
const store = useProductStore()

const searchQuery = ref('')
const showCreateModal = ref(false)

const columns = [
  { key: 'id', label: 'ID', sortable: true },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'created_at', label: 'Created', sortable: true }
]

const fetchData = async () => {
  try {
    await store.fetchAll({ search: searchQuery.value })
  } catch (error) {
    console.error('Error fetching data:', error)
  }
}

const viewDetail = (id) => {
  router.push({ name: 'productDetail', params: { id } })
}

const deleteItem = async (id) => {
  if (confirm('Are you sure you want to delete this item?')) {
    try {
      await store.remove(id)
      await fetchData()
    } catch (error) {
      console.error('Error deleting item:', error)
    }
  }
}

const handleCreate = () => {
  showCreateModal.value = false
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
