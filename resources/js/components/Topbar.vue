<template>
  <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
    <button type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden" @click="$emit('toggleSidebar')">
      <span class="sr-only">Open sidebar</span>
      <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
      </svg>
    </button>

    <div class="h-6 w-px bg-gray-200 lg:hidden"></div>

    <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
      <div class="flex flex-1 items-center">
        <h2 class="text-xl font-semibold text-gray-900">{{ pageTitle }}</h2>
      </div>
      <div class="flex items-center gap-x-4 lg:gap-x-6">
        <button type="button" class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500">
          <span class="sr-only">View notifications</span>
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
          </svg>
        </button>

        <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200"></div>

        <div class="relative">
          <button
            type="button"
            class="flex items-center gap-x-4 px-3 py-2 text-sm font-semibold text-gray-900 hover:bg-gray-50 rounded-md"
            @click="showUserMenu = !showUserMenu"
          >
            <span class="sr-only">Open user menu</span>
            <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white">
              {{ userInitials }}
            </div>
            <span class="hidden lg:flex lg:items-center">
              <span class="ml-2 text-sm font-semibold leading-6 text-gray-900">{{ userName }}</span>
            </span>
          </button>

          <div
            v-if="showUserMenu"
            class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
            @click.stop
          >
            <button @click="handleLogout" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100">
              Sign out
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

defineEmits(['toggleSidebar']);

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const showUserMenu = ref(false);

const pageTitle = computed(() => {
  const titles = {
    dashboard: 'Dashboard',
    customers: 'Customers',
    products: 'Products',
    inventory: 'Inventory',
    pos: 'Point of Sale',
    billing: 'Billing',
    branches: 'Branches',
    fleet: 'Fleet Management',
    crm: 'CRM',
    analytics: 'Analytics',
  };
  const name = route.name?.split('.')[0];
  return titles[name] || 'AutoERP';
});

const userName = computed(() => {
  return authStore.user?.name || 'User';
});

const userInitials = computed(() => {
  const name = userName.value;
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>
