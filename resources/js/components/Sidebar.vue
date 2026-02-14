<template>
  <div>
    <!-- Mobile sidebar -->
    <div v-if="isOpen" class="relative z-50 lg:hidden" @click="$emit('close')">
      <div class="fixed inset-0 bg-gray-900/80"></div>
      <div class="fixed inset-0 flex">
        <div class="relative mr-16 flex w-full max-w-xs flex-1" @click.stop>
          <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-4">
            <div class="flex h-16 shrink-0 items-center">
              <h1 class="text-xl font-bold text-blue-600">AutoERP</h1>
            </div>
            <nav class="flex flex-1 flex-col">
              <ul role="list" class="flex flex-1 flex-col gap-y-7">
                <li>
                  <ul role="list" class="-mx-2 space-y-1">
                    <li v-for="item in menuItems" :key="item.name">
                      <router-link
                        :to="item.to"
                        class="group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6"
                        :class="isActive(item.to) ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50'"
                      >
                        <component :is="item.icon" class="h-6 w-6 shrink-0" />
                        {{ item.name }}
                      </router-link>
                    </li>
                  </ul>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <!-- Desktop sidebar -->
    <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-64 lg:flex-col">
      <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6 pb-4">
        <div class="flex h-16 shrink-0 items-center">
          <h1 class="text-xl font-bold text-blue-600">AutoERP</h1>
        </div>
        <nav class="flex flex-1 flex-col">
          <ul role="list" class="flex flex-1 flex-col gap-y-7">
            <li>
              <ul role="list" class="-mx-2 space-y-1">
                <li v-for="item in menuItems" :key="item.name">
                  <router-link
                    :to="item.to"
                    class="group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6"
                    :class="isActive(item.to) ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50'"
                  >
                    <component :is="item.icon" class="h-6 w-6 shrink-0" />
                    {{ item.name }}
                  </router-link>
                </li>
              </ul>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router';

defineProps({
  isOpen: Boolean,
});

defineEmits(['close']);

const route = useRoute();

const menuItems = [
  { name: 'Dashboard', to: '/dashboard', icon: 'DashboardIcon' },
  { name: 'Customers', to: '/customers', icon: 'UsersIcon' },
  { name: 'Products', to: '/products', icon: 'CubeIcon' },
  { name: 'Inventory', to: '/inventory', icon: 'ArchiveIcon' },
  { name: 'POS', to: '/pos', icon: 'ShoppingIcon' },
  { name: 'Billing', to: '/billing', icon: 'DocumentIcon' },
  { name: 'Branches', to: '/branches', icon: 'BuildingIcon' },
  { name: 'Fleet', to: '/fleet', icon: 'TruckIcon' },
  { name: 'CRM', to: '/crm', icon: 'ChatIcon' },
  { name: 'Analytics', to: '/analytics', icon: 'ChartIcon' },
];

const isActive = (path) => {
  return route.path === path || route.path.startsWith(path + '/');
};
</script>
