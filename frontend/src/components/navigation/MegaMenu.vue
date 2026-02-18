<template>
  <div
    v-click-outside="closeMegaMenu"
    class="mega-menu relative"
  >
    <!-- Menu Trigger -->
    <button
      class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-gray-900 dark:hover:text-white focus:outline-none"
      :class="{ 'text-primary-600 dark:text-primary-400': isOpen }"
      @click="toggleMegaMenu"
    >
      <span>{{ label }}</span>
      <svg
        class="ml-2 w-4 h-4 transition-transform"
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

    <!-- Mega Menu Dropdown -->
    <transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-1"
    >
      <div
        v-show="isOpen"
        class="absolute left-0 z-50 mt-2 w-screen max-w-6xl transform"
        :class="position === 'right' ? 'right-0 left-auto' : ''"
      >
        <div class="rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 overflow-hidden">
          <div
            class="relative grid gap-6 bg-white dark:bg-gray-800 px-5 py-6 sm:gap-8 sm:p-8"
            :class="gridClass"
          >
            <!-- Menu Sections -->
            <div
              v-for="section in sections"
              :key="section.id"
              class="mega-menu-section"
            >
              <!-- Section Title -->
              <h3
                v-if="section.title"
                class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4"
              >
                {{ section.title }}
              </h3>

              <!-- Section Items -->
              <div class="space-y-2">
                <router-link
                  v-for="item in section.items"
                  :key="item.id"
                  :to="item.path"
                  class="flex items-start p-3 -m-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                  @click="closeMegaMenu"
                >
                  <!-- Icon -->
                  <div
                    v-if="item.icon"
                    class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-md bg-primary-500 text-white"
                  >
                    <component
                      :is="getIcon(item.icon)"
                      class="h-6 w-6"
                    />
                  </div>

                  <!-- Content -->
                  <div class="ml-4">
                    <p class="text-base font-medium text-gray-900 dark:text-white">
                      {{ item.label }}
                    </p>
                    <p
                      v-if="item.description"
                      class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                    >
                      {{ item.description }}
                    </p>
                  </div>
                </router-link>
              </div>
            </div>
          </div>

          <!-- Featured Section (Optional) -->
          <div
            v-if="featured"
            class="bg-gray-50 dark:bg-gray-700 px-5 py-5 sm:px-8 sm:py-8"
          >
            <div>
              <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                {{ featured.title }}
              </h3>
              <ul class="mt-4 space-y-4">
                <li
                  v-for="item in featured.items"
                  :key="item.id"
                  class="text-base truncate"
                >
                  <router-link
                    :to="item.path"
                    class="font-medium text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                    @click="closeMegaMenu"
                  >
                    {{ item.label }}
                  </router-link>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

interface MenuItem {
  id: string;
  label: string;
  path: string;
  icon?: string;
  description?: string;
}

interface MenuSection {
  id: string;
  title?: string;
  items: MenuItem[];
}

interface FeaturedSection {
  title: string;
  items: MenuItem[];
}

interface Props {
  label: string;
  sections: MenuSection[];
  featured?: FeaturedSection;
  columns?: number;
  position?: 'left' | 'right';
}

const props = withDefaults(defineProps<Props>(), {
  columns: 3,
  position: 'left',
});

const isOpen = ref(false);

const gridClass = computed(() => {
  return `grid-cols-${props.columns}`;
});

const toggleMegaMenu = () => {
  isOpen.value = !isOpen.value;
};

const closeMegaMenu = () => {
  isOpen.value = false;
};

const getIcon = (iconName: string) => {
  // Dynamic icon loading - you can customize this based on your icon library
  return () => import(`@heroicons/vue/24/outline/${iconName}.js`);
};

// Custom directive for click outside
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
</script>

<style scoped>
.mega-menu-section {
  @apply min-w-0;
}
</style>
