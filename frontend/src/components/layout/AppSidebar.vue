<template>
  <aside
    class="fixed left-0 top-16 bottom-0 bg-gray-900 text-white transition-all duration-300 z-30 overflow-y-auto"
    :class="{ 'w-64': !collapsed, 'w-16': collapsed }"
  >
    <nav class="py-4">
      <router-link
        v-for="item in menuItems"
        :key="item.name"
        :to="item.to"
        class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors"
        :class="{ 'bg-gray-800 text-white': $route.path.startsWith(item.to) }"
      >
        <component :is="item.icon" class="h-6 w-6 flex-shrink-0" />
        <span v-if="!collapsed" class="ml-3">{{ item.label }}</span>
      </router-link>
      
      <div v-for="section in menuSections" :key="section.name" class="mt-4">
        <div v-if="!collapsed" class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase">
          {{ section.label }}
        </div>
        <router-link
          v-for="item in section.items"
          :key="item.name"
          :to="item.to"
          class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors"
          :class="{ 'bg-gray-800 text-white': $route.path.startsWith(item.to) }"
        >
          <component :is="item.icon" class="h-6 w-6 flex-shrink-0" />
          <span v-if="!collapsed" class="ml-3">{{ item.label }}</span>
        </router-link>
      </div>
    </nav>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useUiStore } from '@stores/ui'
import {
  HomeIcon,
  UsersIcon,
  PhoneIcon,
  TruckIcon,
  CubeIcon,
  DocumentTextIcon,
  CalendarIcon
} from '@heroicons/vue/24/outline'

const uiStore = useUiStore()
const collapsed = computed(() => uiStore.sidebarCollapsed)

const menuItems = [
  { name: 'dashboard', label: 'Dashboard', to: '/', icon: HomeIcon }
]

const menuSections = [
  {
    name: 'crm',
    label: 'CRM',
    items: [
      { name: 'customers', label: 'Customers', to: '/crm/customers', icon: UsersIcon },
      { name: 'contacts', label: 'Contacts', to: '/crm/contacts', icon: PhoneIcon },
      { name: 'vehicles', label: 'Vehicles', to: '/crm/vehicles', icon: TruckIcon }
    ]
  },
  {
    name: 'inventory',
    label: 'Inventory',
    items: [
      { name: 'products', label: 'Products', to: '/inventory/products', icon: CubeIcon }
    ]
  },
  {
    name: 'billing',
    label: 'Billing',
    items: [
      { name: 'invoices', label: 'Invoices', to: '/billing/invoices', icon: DocumentTextIcon }
    ]
  },
  {
    name: 'appointments',
    label: 'Appointments',
    items: [
      { name: 'appointments', label: 'Appointments', to: '/appointments', icon: CalendarIcon }
    ]
  }
]
</script>
