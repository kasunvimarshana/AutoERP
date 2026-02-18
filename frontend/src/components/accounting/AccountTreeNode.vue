<template>
  <div class="account-tree-node">
    <div
      :class="[
        'flex items-center justify-between py-2 px-3 rounded hover:bg-gray-50 cursor-pointer',
        { 'font-semibold': account.is_header }
      ]"
      :style="{ paddingLeft: `${level * 24 + 12}px` }"
      @click="emit('view', account.id)"
    >
      <div class="flex items-center space-x-3 flex-1">
        <!-- Expand/Collapse Icon -->
        <button
          v-if="account.children && account.children.length > 0"
          class="text-gray-400 hover:text-gray-600 focus:outline-none"
          @click.stop="toggleExpanded"
        >
          <svg
            class="h-4 w-4 transition-transform"
            :class="{ 'transform rotate-90': expanded }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </button>
        <span
          v-else
          class="w-4"
        />

        <!-- Account Code -->
        <span class="text-sm font-mono text-gray-700">
          {{ account.code }}
        </span>

        <!-- Account Name -->
        <span class="text-sm text-gray-900">
          {{ account.name }}
        </span>

        <!-- Type Badge -->
        <span
          :class="getTypeClass(account.type)"
          class="px-2 py-0.5 text-xs rounded-full"
        >
          {{ formatType(account.type) }}
        </span>

        <!-- Header Badge -->
        <span
          v-if="account.is_header"
          class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-700"
        >
          Header
        </span>
      </div>

      <!-- Balance -->
      <div class="flex items-center space-x-4">
        <span class="text-sm text-gray-900 font-medium">
          {{ formatCurrency(account.balance, account.currency) }}
        </span>

        <!-- Status -->
        <span
          :class="getStatusClass(account.is_active)"
          class="px-2 py-0.5 text-xs rounded-full"
        >
          {{ account.is_active ? 'Active' : 'Inactive' }}
        </span>

        <!-- Actions -->
        <div class="flex items-center space-x-2">
          <button
            v-if="hasPermission('accounting.accounts.update')"
            class="text-indigo-600 hover:text-indigo-900 text-xs"
            @click.stop="emit('edit', account.id)"
          >
            Edit
          </button>
          <button
            v-if="hasPermission('accounting.accounts.delete')"
            class="text-red-600 hover:text-red-900 text-xs"
            @click.stop="emit('delete', account.id)"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Children -->
    <div
      v-if="expanded && account.children && account.children.length > 0"
      class="mt-1"
    >
      <AccountTreeNode
        v-for="child in account.children"
        :key="child.id"
        :account="child"
        :level="level + 1"
        @view="emit('view', $event)"
        @edit="emit('edit', $event)"
        @delete="emit('delete', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { Account } from '@/types/accounting'
import { usePermissions } from '@/composables/usePermissions'

interface Props {
  account: Account
  level: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  view: [id: number]
  edit: [id: number]
  delete: [id: number]
}>()

const { hasPermission } = usePermissions()

const expanded = ref(true)

const toggleExpanded = () => {
  expanded.value = !expanded.value
}

const formatType = (type: string): string => {
  return type.charAt(0).toUpperCase() + type.slice(1)
}

const formatCurrency = (amount: number, currency: string = 'USD'): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency
  }).format(amount)
}

const getTypeClass = (type: string): string => {
  const classes: Record<string, string> = {
    asset: 'bg-blue-100 text-blue-800',
    liability: 'bg-red-100 text-red-800',
    equity: 'bg-purple-100 text-purple-800',
    revenue: 'bg-green-100 text-green-800',
    expense: 'bg-orange-100 text-orange-800',
    contra: 'bg-gray-100 text-gray-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const getStatusClass = (isActive: boolean): string => {
  return isActive
    ? 'bg-green-100 text-green-800'
    : 'bg-gray-100 text-gray-800'
}
</script>
