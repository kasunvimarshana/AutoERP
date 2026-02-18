<template>
  <nav v-if="breadcrumbs.length > 0" class="breadcrumb">
    <ol class="breadcrumb-list">
      <li v-for="(crumb, index) in breadcrumbs" :key="index" class="breadcrumb-item">
        <router-link 
          v-if="index < breadcrumbs.length - 1"
          :to="crumb.path"
          class="breadcrumb-link"
        >
          {{ crumb.name }}
        </router-link>
        <span v-else class="breadcrumb-current">{{ crumb.name }}</span>
        <ChevronRightIcon v-if="index < breadcrumbs.length - 1" class="separator" />
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { ChevronRightIcon } from '@heroicons/vue/24/outline'

const route = useRoute()

const breadcrumbs = computed(() => {
  const crumbs = []
  const pathArray = route.path.split('/').filter(p => p)
  
  let currentPath = ''
  pathArray.forEach((segment) => {
    currentPath += `/${segment}`
    const name = segment
      .split('-')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ')
    
    crumbs.push({
      name: name,
      path: currentPath
    })
  })
  
  return crumbs
})
</script>

<style scoped>
.breadcrumb {
  padding: 16px 24px;
  background: white;
  border-bottom: 1px solid #e5e7eb;
}

.breadcrumb-list {
  display: flex;
  align-items: center;
  list-style: none;
  gap: 8px;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.breadcrumb-link {
  color: #6b7280;
  text-decoration: none;
  font-size: 14px;
  transition: color 0.2s;
}

.breadcrumb-link:hover {
  color: #3b82f6;
}

.breadcrumb-current {
  color: #1f2937;
  font-size: 14px;
  font-weight: 500;
}

.separator {
  width: 16px;
  height: 16px;
  color: #d1d5db;
}
</style>
