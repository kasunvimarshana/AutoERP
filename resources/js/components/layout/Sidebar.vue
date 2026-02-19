<template>
  <div class="flex h-full flex-col bg-gray-800 w-64">
    <div class="flex flex-1 flex-col overflow-y-auto pt-5 pb-4">
      <div class="flex flex-shrink-0 items-center px-4 mb-5">
        <h2 class="text-white text-xl font-bold">{{ title }}</h2>
      </div>
      <nav class="mt-5 flex-1 space-y-1 px-2">
        <router-link
          v-for="item in menuItems"
          :key="item.name"
          :to="item.to"
          v-slot="{ isActive }"
          custom
        >
          <a
            :href="item.to"
            @click.prevent="$router.push(item.to)"
            :class="[
              isActive
                ? 'bg-gray-900 text-white'
                : 'text-gray-300 hover:bg-gray-700 hover:text-white',
              'group flex items-center px-2 py-2 text-sm font-medium rounded-md'
            ]"
          >
            <component
              v-if="item.icon"
              :is="item.icon"
              :class="[
                isActive ? 'text-gray-300' : 'text-gray-400 group-hover:text-gray-300',
                'mr-3 h-6 w-6 flex-shrink-0'
              ]"
              aria-hidden="true"
            />
            {{ item.name }}
            <span
              v-if="item.badge"
              :class="[
                isActive ? 'bg-gray-800' : 'bg-gray-900 group-hover:bg-gray-800',
                'ml-auto inline-block py-0.5 px-3 text-xs rounded-full'
              ]"
            >
              {{ item.badge }}
            </span>
          </a>
        </router-link>
      </nav>
    </div>
    <div v-if="$slots.footer" class="flex flex-shrink-0 border-t border-gray-700 p-4">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: 'Menu',
  },
  menuItems: {
    type: Array,
    required: true,
  },
});
</script>
