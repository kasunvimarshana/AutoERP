<template>
  <nav class="dynamic-navigation">
    <div
      v-if="loading"
      class="loading"
    >
      Loading modules...
    </div>
    
    <div
      v-else-if="error"
      class="error"
    >
      {{ error }}
    </div>
    
    <ul
      v-else
      class="nav-list"
    >
      <li
        v-for="item in navigationItems"
        :key="item.id"
        class="nav-item"
      >
        <router-link 
          :to="item.path" 
          class="nav-link"
          :class="{ active: isActive(item.path) }"
        >
          <span class="nav-icon">{{ getIcon(item.icon) }}</span>
          <span class="nav-label">{{ item.name }}</span>
        </router-link>
        
        <ul
          v-if="item.children && item.children.length"
          class="nav-children"
        >
          <li
            v-for="child in item.children"
            :key="child.id"
            class="nav-child"
          >
            <router-link 
              v-if="hasPermission(child.permissions)" 
              :to="child.path"
              class="nav-link nav-link-child"
              :class="{ active: isActive(child.path) }"
            >
              <span class="nav-icon">{{ getIcon(child.icon) }}</span>
              <span class="nav-label">{{ child.name }}</span>
            </router-link>
          </li>
        </ul>
      </li>
    </ul>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useModuleStore } from '@/stores/modules';
import { useAuthStore } from '@/stores/auth';

const moduleStore = useModuleStore();
const authStore = useAuthStore();
const route = useRoute();

const loading = computed(() => moduleStore.loading);
const error = computed(() => moduleStore.error);

const navigationItems = computed(() => {
  const userPermissions = authStore.permissions || [];
  return moduleStore.buildNavigation(userPermissions);
});

const isActive = (path: string) => {
  return route.path.startsWith(path);
};

const hasPermission = (permissions: string[] = []) => {
  if (permissions.length === 0) return true;
  return authStore.hasAnyPermission(permissions);
};

const getIcon = (icon: string) => {
  const iconMap: Record<string, string> = {
    'warehouse': '🏭',
    'cube': '📦',
    'building': '🏢',
    'shopping-cart': '🛒',
  };
  return iconMap[icon] || '📄';
};
</script>

<style scoped>
.dynamic-navigation {
  padding: 1rem;
}

.nav-link {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  text-decoration: none;
  color: #374151;
  border-radius: 0.5rem;
}

.nav-link:hover {
  background-color: #f3f4f6;
}

.nav-link.active {
  background-color: #3b82f6;
  color: white;
}
</style>
