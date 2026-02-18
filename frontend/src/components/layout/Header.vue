<template>
  <header class="bg-white shadow-sm">
    <div class="flex items-center justify-between h-16 px-6">
      <!-- Left Section -->
      <div class="flex items-center">
        <button
          class="text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700"
          @click="$emit('toggle-sidebar')"
        >
          <Bars3Icon class="h-6 w-6" />
        </button>

        <!-- Breadcrumbs or Page Title -->
        <div class="ml-4">
          <h1 class="text-xl font-semibold text-gray-800">
            {{ pageTitle }}
          </h1>
        </div>
      </div>

      <!-- Right Section -->
      <div class="flex items-center space-x-4">
        <!-- Tenant Selector (if user has multiple tenants) -->
        <div
          v-if="tenantName"
          class="text-sm text-gray-600"
        >
          <span class="font-medium">{{ tenantName }}</span>
        </div>

        <!-- Notifications -->
        <button
          class="text-gray-500 hover:text-gray-700 focus:outline-none"
          @click="showNotifications"
        >
          <BellIcon class="h-6 w-6" />
        </button>

        <!-- User Menu -->
        <Menu
          as="div"
          class="relative"
        >
          <MenuButton
            class="flex items-center space-x-2 text-sm focus:outline-none"
          >
            <img
              v-if="user?.avatar"
              :src="user.avatar"
              :alt="user.name"
              class="h-8 w-8 rounded-full"
            >
            <div
              v-else
              class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-medium"
            >
              {{ userInitials }}
            </div>
            <span class="hidden md:block text-gray-700">{{ user?.name }}</span>
            <ChevronDownIcon class="h-4 w-4 text-gray-500" />
          </MenuButton>

          <MenuItems
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 focus:outline-none"
          >
            <MenuItem v-slot="{ active }">
              <a
                href="/profile"
                :class="[
                  'block px-4 py-2 text-sm text-gray-700',
                  active && 'bg-gray-100',
                ]"
              >
                Profile
              </a>
            </MenuItem>
            <MenuItem v-slot="{ active }">
              <a
                href="/settings"
                :class="[
                  'block px-4 py-2 text-sm text-gray-700',
                  active && 'bg-gray-100',
                ]"
              >
                Settings
              </a>
            </MenuItem>
            <MenuItem v-slot="{ active }">
              <button
                :class="[
                  'block w-full text-left px-4 py-2 text-sm text-gray-700',
                  active && 'bg-gray-100',
                ]"
                @click="handleLogout"
              >
                Logout
              </button>
            </MenuItem>
          </MenuItems>
        </Menu>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { Bars3Icon, BellIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';

defineEmits(['toggle-sidebar']);

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const tenantStore = useTenantStore();

const user = computed(() => authStore.user);
const tenantName = computed(() => tenantStore.tenantName);
const pageTitle = computed(() => route.meta.title || 'Dashboard');

const userInitials = computed(() => {
  if (!user.value) return '';
  return user.value.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
});

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};

const showNotifications = () => {
  // Open notifications panel
  console.log('Show notifications');
};
</script>
