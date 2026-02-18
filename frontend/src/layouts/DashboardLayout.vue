<template>
  <div class="dashboard-layout">
    <Sidebar :isMobileOpen="isMobileMenuOpen" @close="isMobileMenuOpen = false" />
    
    <div class="main-content">
      <Header @toggleMobile="isMobileMenuOpen = !isMobileMenuOpen" />
      <Breadcrumb />
      
      <main class="content-area">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
    
    <NotificationContainer />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Sidebar from '../components/layout/Sidebar.vue'
import Header from '../components/layout/Header.vue'
import Breadcrumb from '../components/layout/Breadcrumb.vue'
import NotificationContainer from '../components/common/NotificationContainer.vue'

const isMobileMenuOpen = ref(false)
</script>

<style scoped>
.dashboard-layout {
  display: flex;
  min-height: 100vh;
  background: #f5f5f5;
}

.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  margin-left: 250px;
  transition: margin-left 0.3s ease;
}

@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
  }
}

.content-area {
  flex: 1;
  padding: 24px;
  overflow-y: auto;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
