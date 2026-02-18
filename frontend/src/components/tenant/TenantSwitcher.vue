<template>
  <div
    v-click-outside="close"
    class="tenant-switcher relative"
  >
    <!-- Current Tenant Button -->
    <button
      class="flex items-center space-x-2 px-3 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
      :class="{ 'bg-gray-100 dark:bg-gray-700': isOpen }"
      @click="toggle"
    >
      <!-- Tenant Icon/Logo -->
      <div class="flex-shrink-0">
        <div
          v-if="currentTenant?.logo"
          class="w-8 h-8 rounded-full overflow-hidden"
        >
          <img
            :src="currentTenant.logo"
            :alt="currentTenant.name"
            class="w-full h-full object-cover"
          >
        </div>
        <div
          v-else
          class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center"
        >
          <span class="text-white text-sm font-medium">
            {{ getTenantInitials(currentTenant) }}
          </span>
        </div>
      </div>

      <!-- Tenant Name -->
      <div class="hidden sm:block text-left">
        <div class="text-sm font-medium text-gray-900 dark:text-white">
          {{ currentTenant?.name || 'Select Tenant' }}
        </div>
        <div
          v-if="currentTenant?.plan"
          class="text-xs text-gray-500 dark:text-gray-400"
        >
          {{ currentTenant.plan }}
        </div>
      </div>

      <!-- Dropdown Arrow -->
      <svg
        class="w-4 h-4 text-gray-400 transition-transform"
        :class="{ 'rotate-180': isOpen }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 9l-7 7-7-7"
        />
      </svg>
    </button>

    <!-- Dropdown Menu -->
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-show="isOpen"
        class="absolute right-0 z-50 mt-2 w-80 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5"
      >
        <!-- Loading State -->
        <div
          v-if="loading"
          class="px-4 py-3 text-center text-sm text-gray-500"
        >
          Loading tenants...
        </div>

        <!-- Tenant List -->
        <div
          v-else
          class="py-1 max-h-96 overflow-y-auto"
        >
          <!-- Current Tenant -->
          <div class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Current Organization
          </div>
          <div class="px-4 py-2 bg-primary-50 dark:bg-primary-900/20 border-l-4 border-primary-600">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div
                  v-if="currentTenant?.logo"
                  class="w-10 h-10 rounded-full overflow-hidden"
                >
                  <img
                    :src="currentTenant.logo"
                    :alt="currentTenant.name"
                    class="w-full h-full object-cover"
                  >
                </div>
                <div
                  v-else
                  class="w-10 h-10 rounded-full bg-primary-600 flex items-center justify-center"
                >
                  <span class="text-white text-sm font-medium">
                    {{ getTenantInitials(currentTenant) }}
                  </span>
                </div>
              </div>
              <div class="ml-3 flex-1">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ currentTenant?.name }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ currentTenant?.plan }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useTenantStore } from '@/stores/tenant';

interface Tenant {
  id: string;
  name: string;
  domain: string;
  plan: string;
  logo?: string;
}

const tenantStore = useTenantStore();

const isOpen = ref(false);
const loading = ref(false);

const currentTenant = computed(() => tenantStore.currentTenant);

const toggle = () => {
  isOpen.value = !isOpen.value;
};

const close = () => {
  isOpen.value = false;
};

const getTenantInitials = (tenant: Tenant | null | undefined) => {
  if (!tenant?.name) return '?';
  return tenant.name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
};

const vClickOutside = {
  mounted(el: HTMLElement, binding: any) {
    el.clickOutsideEvent = (event: Event) => {
      if (!(el === event.target || el.contains(event.target as Node))) {
        binding.value();
      }
    };
    document.addEventListener('click', el.clickOutsideEvent);
  },
  unmounted(el: HTMLElement) {
    document.removeEventListener('click', (el as any).clickOutsideEvent);
  },
};

onMounted(() => {
  if (!currentTenant.value) {
    tenantStore.loadCurrentTenant();
  }
});
</script>
