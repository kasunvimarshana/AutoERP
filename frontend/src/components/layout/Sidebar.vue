<template>
  <aside
    :class="[
      'bg-gray-900 text-white w-64 fixed inset-y-0 left-0 transform transition-transform duration-300 ease-in-out z-30',
      open ? 'translate-x-0' : '-translate-x-full',
      'md:relative md:translate-x-0',
    ]"
  >
    <!-- Logo -->
    <div class="flex items-center justify-between h-16 px-6 bg-gray-800">
      <div class="flex items-center">
        <img
          v-if="tenantLogo"
          :src="tenantLogo"
          :alt="tenantName"
          class="h-8 w-auto"
        >
        <span
          v-else
          class="text-xl font-bold"
        >AutoERP</span>
      </div>
      <button
        class="md:hidden text-gray-400 hover:text-white"
        @click="$emit('close')"
      >
        <XMarkIcon class="h-6 w-6" />
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 overflow-y-auto">
      <div
        v-for="item in navigation"
        :key="item.name"
        class="mb-2"
      >
        <router-link
          v-if="!item.children && canAccess(item)"
          :to="item.route || '/'"
          :class="[
            'flex items-center px-4 py-3 rounded-lg transition-colors',
            isActive(item.route)
              ? 'bg-blue-600 text-white'
              : 'text-gray-300 hover:bg-gray-800 hover:text-white',
          ]"
        >
          <component
            :is="getIcon(item.icon)"
            v-if="item.icon"
            class="h-5 w-5 mr-3"
          />
          <span>{{ item.label }}</span>
          <span
            v-if="item.badge"
            :class="[
              'ml-auto px-2 py-1 text-xs rounded-full',
              badgeClasses(item.badge.variant),
            ]"
          >
            {{ item.badge.text }}
          </span>
        </router-link>

        <div v-else-if="item.children && canAccess(item)">
          <button
            :class="[
              'w-full flex items-center justify-between px-4 py-3 rounded-lg transition-colors text-gray-300 hover:bg-gray-800 hover:text-white',
            ]"
            @click="toggleGroup(item.name)"
          >
            <div class="flex items-center">
              <component
                :is="getIcon(item.icon)"
                v-if="item.icon"
                class="h-5 w-5 mr-3"
              />
              <span>{{ item.label }}</span>
            </div>
            <ChevronDownIcon
              :class="[
                'h-4 w-4 transition-transform',
                expandedGroups.includes(item.name) && 'transform rotate-180',
              ]"
            />
          </button>

          <div
            v-show="expandedGroups.includes(item.name)"
            class="ml-8 mt-2 space-y-2"
          >
            <router-link
              v-for="child in item.children"
              v-show="canAccess(child)"
              :key="child.name"
              :to="child.route || '/'"
              :class="[
                'block px-4 py-2 rounded-lg text-sm transition-colors',
                isActive(child.route)
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-400 hover:bg-gray-800 hover:text-white',
              ]"
            >
              {{ child.label }}
            </router-link>
          </div>
        </div>
      </div>
    </nav>
  </aside>

  <!-- Overlay for mobile -->
  <div
    v-if="open"
    class="fixed inset-0 bg-black bg-opacity-50 z-20 md:hidden"
    @click="$emit('close')"
  />
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import { XMarkIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';
import {
  HomeIcon,
  UsersIcon,
  CogIcon,
  DocumentTextIcon,
  ChartBarIcon,
} from '@heroicons/vue/24/outline';
import { useTenantStore } from '@/stores/tenant';
import { usePermissions } from '@/composables/usePermissions';
import type { NavigationItem } from '@/types';

interface Props {
  open: boolean;
}

defineProps<Props>();
defineEmits(['close']);

const route = useRoute();
const tenantStore = useTenantStore();
const { hasAnyPermission } = usePermissions();

const tenantName = computed(() => tenantStore.tenantName);
const tenantLogo = computed(() => tenantStore.tenantLogo);
const expandedGroups = ref<string[]>(['modules']);

// Navigation items (these would typically come from metadata API)
const navigation = computed<NavigationItem[]>(() => [
  {
    name: 'dashboard',
    label: 'Dashboard',
    icon: 'HomeIcon',
    route: '/',
    permissions: [],
  },
  {
    name: 'modules',
    label: 'Modules',
    icon: 'DocumentTextIcon',
    permissions: [],
    children: [
      {
        name: 'customers',
        label: 'Customers',
        route: '/modules/customers',
        permissions: ['customers.view'],
      },
      {
        name: 'products',
        label: 'Products',
        route: '/modules/products',
        permissions: ['products.view'],
      },
      {
        name: 'orders',
        label: 'Orders',
        route: '/modules/orders',
        permissions: ['orders.view'],
      },
    ],
  },
  {
    name: 'reports',
    label: 'Reports',
    icon: 'ChartBarIcon',
    route: '/reports',
    permissions: ['reports.view'],
  },
  {
    name: 'users',
    label: 'Users',
    icon: 'UsersIcon',
    route: '/users',
    permissions: ['users.view'],
  },
  {
    name: 'settings',
    label: 'Settings',
    icon: 'CogIcon',
    route: '/settings',
    permissions: ['settings.view'],
  },
]);

const iconMap: Record<string, any> = {
  HomeIcon,
  UsersIcon,
  CogIcon,
  DocumentTextIcon,
  ChartBarIcon,
};

const getIcon = (iconName: string) => {
  return iconMap[iconName] || DocumentTextIcon;
};

const isActive = (path?: string) => {
  if (!path) return false;
  return route.path === path || route.path.startsWith(path + '/');
};

const canAccess = (item: NavigationItem): boolean => {
  if (!item.permissions || item.permissions.length === 0) return true;
  return hasAnyPermission(item.permissions);
};

const toggleGroup = (name: string) => {
  const index = expandedGroups.value.indexOf(name);
  if (index > -1) {
    expandedGroups.value.splice(index, 1);
  } else {
    expandedGroups.value.push(name);
  }
};

const badgeClasses = (variant: string) => {
  const classes: Record<string, string> = {
    primary: 'bg-blue-600 text-white',
    success: 'bg-green-600 text-white',
    warning: 'bg-yellow-600 text-white',
    danger: 'bg-red-600 text-white',
  };
  return classes[variant] || classes.primary;
};
</script>
