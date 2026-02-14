<template>
  <div class="min-h-screen bg-gray-100">
    <Sidebar :isOpen="sidebarOpen" @close="sidebarOpen = false" />
    
    <div class="lg:pl-64">
      <Topbar @toggleSidebar="sidebarOpen = !sidebarOpen" />
      
      <main class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <router-view />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Sidebar from '../components/Sidebar.vue';
import Topbar from '../components/Topbar.vue';

const sidebarOpen = ref(false);
</script>
