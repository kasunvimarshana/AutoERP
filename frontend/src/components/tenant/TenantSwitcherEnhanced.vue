<template>
  <div class="tenant-switcher-wrapper">
    <Menu
      as="div"
      class="relative inline-block text-left"
    >
      <div>
        <MenuButton
          class="inline-flex w-full justify-between items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
          <div class="flex items-center">
            <div
              v-if="currentTenant?.logo"
              class="mr-3 h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden"
            >
              <img
                :src="currentTenant.logo"
                :alt="currentTenant.name"
                class="h-full w-full object-cover"
              >
            </div>
            <div
              v-else
              class="mr-3 h-6 w-6 rounded-full bg-primary-600 flex items-center justify-center text-white text-xs font-bold"
            >
              {{ getInitials(currentTenant?.name || 'T') }}
            </div>
            <span class="truncate max-w-xs">
              {{ currentTenant?.name || 'Select Tenant' }}
            </span>
          </div>
          <ChevronDownIcon
            class="ml-2 h-5 w-5 text-gray-400"
            aria-hidden="true"
          />
        </MenuButton>
      </div>

      <transition
        enter-active-class="transition ease-out duration-100"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <MenuItems
          class="absolute right-0 z-10 mt-2 w-72 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
        >
          <div class="py-1">
            <!-- Search -->
            <div
              v-if="tenants.length > 5"
              class="px-3 py-2 border-b border-gray-200"
            >
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search tenants..."
                class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary-500"
              >
            </div>

            <!-- Loading State -->
            <div
              v-if="loading"
              class="px-4 py-3 text-sm text-gray-500 text-center"
            >
              <div class="animate-spin inline-block w-5 h-5 border-2 border-primary-600 border-t-transparent rounded-full" />
              <span class="ml-2">Loading tenants...</span>
            </div>

            <!-- Tenant List -->
            <div
              v-else
              class="max-h-60 overflow-y-auto"
            >
              <MenuItem
                v-for="tenant in filteredTenants"
                :key="tenant.id"
                v-slot="{ active }"
              >
                <button
                  class="w-full text-left px-4 py-2 text-sm flex items-center"
                  :class="[
                    active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                    tenant.id === currentTenant?.id ? 'bg-primary-50 text-primary-700' : ''
                  ]"
                  @click="switchTenant(tenant)"
                >
                  <div
                    v-if="tenant.logo"
                    class="mr-3 h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center overflow-hidden"
                  >
                    <img
                      :src="tenant.logo"
                      :alt="tenant.name"
                      class="h-full w-full object-cover"
                    >
                  </div>
                  <div
                    v-else
                    class="mr-3 h-8 w-8 rounded-full flex items-center justify-center text-white text-sm font-bold"
                    :class="getTenantColorClass(tenant.id)"
                  >
                    {{ getInitials(tenant.name) }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="font-medium truncate">
                      {{ tenant.name }}
                    </div>
                    <div class="text-xs text-gray-500 truncate">
                      {{ tenant.domain }}
                    </div>
                  </div>
                  <CheckIcon
                    v-if="tenant.id === currentTenant?.id"
                    class="ml-2 h-5 w-5 text-primary-600"
                    aria-hidden="true"
                  />
                </button>
              </MenuItem>

              <!-- No Results -->
              <div
                v-if="filteredTenants.length === 0"
                class="px-4 py-3 text-sm text-gray-500 text-center"
              >
                No tenants found
              </div>
            </div>

            <!-- Create New Tenant (if allowed) -->
            <div
              v-if="canCreateTenant"
              class="border-t border-gray-200"
            >
              <MenuItem v-slot="{ active }">
                <button
                  class="w-full text-left px-4 py-2 text-sm flex items-center"
                  :class="active ? 'bg-gray-100 text-gray-900' : 'text-gray-700'"
                  @click="createNewTenant"
                >
                  <PlusCircleIcon class="mr-3 h-5 w-5 text-primary-600" />
                  Create New Tenant
                </button>
              </MenuItem>
            </div>
          </div>
        </MenuItems>
      </transition>
    </Menu>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { ChevronDownIcon, CheckIcon, PlusCircleIcon } from '@heroicons/vue/24/outline';
import { useTenantStore } from '@/stores/tenant';
import { useAuthStore } from '@/stores/auth';
import { useMetadataStore } from '@/stores/metadata';
import api from '@/api/client';

interface Tenant {
  id: number;
  name: string;
  domain: string;
  logo?: string;
  slug?: string;
}

const router = useRouter();
const tenantStore = useTenantStore();
const authStore = useAuthStore();
const metadataStore = useMetadataStore();

// State
const loading = ref(false);
const searchQuery = ref('');
const tenants = ref<Tenant[]>([]);

// Computed
const currentTenant = computed(() => tenantStore.currentTenant);

const filteredTenants = computed(() => {
  if (!searchQuery.value) return tenants.value;
  
  const query = searchQuery.value.toLowerCase();
  return tenants.value.filter(tenant =>
    tenant.name.toLowerCase().includes(query) ||
    tenant.domain.toLowerCase().includes(query)
  );
});

const canCreateTenant = computed(() => {
  return authStore.hasPermission('tenants.create') || 
         authStore.hasRole('super-admin');
});

// Methods
const loadTenants = async () => {
  loading.value = true;
  try {
    const response = await api.get('/tenants');
    if (response.data.success) {
      tenants.value = response.data.data;
    }
  } catch (error) {
    console.error('Failed to load tenants:', error);
  } finally {
    loading.value = false;
  }
};

const switchTenant = async (tenant: Tenant) => {
  if (tenant.id === currentTenant.value?.id) return;

  loading.value = true;
  try {
    // Call API to switch tenant context
    const response = await api.post('/tenants/switch', {
      tenant_id: tenant.id
    });

    if (response.data.success) {
      // Update tenant store
      tenantStore.setCurrentTenant(tenant);

      // Reload metadata for new tenant
      await metadataStore.loadTenantConfiguration();

      // Reload user permissions
      await metadataStore.loadUserPermissions();

      // Optionally redirect to dashboard
      router.push({ name: 'dashboard' });

      // Show success message
      console.log(`Switched to tenant: ${tenant.name}`);
    }
  } catch (error) {
    console.error('Failed to switch tenant:', error);
  } finally {
    loading.value = false;
  }
};

const createNewTenant = () => {
  router.push({ name: 'tenants-create' });
};

const getInitials = (name: string): string => {
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .substring(0, 2);
};

const getTenantColorClass = (tenantId: number): string => {
  const colors = [
    'bg-blue-600',
    'bg-green-600',
    'bg-yellow-600',
    'bg-red-600',
    'bg-purple-600',
    'bg-pink-600',
    'bg-indigo-600'
  ];
  return colors[tenantId % colors.length];
};

// Lifecycle
onMounted(() => {
  loadTenants();
});
</script>

<style scoped>
.tenant-switcher-wrapper {
  @apply min-w-0;
}
</style>
