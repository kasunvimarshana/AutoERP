<template>
  <header class="header">
    <button class="mobile-menu-btn" @click="$emit('toggleMobile')">
      <Bars3Icon class="icon" />
    </button>
    
    <div class="header-actions">
      <button class="icon-btn" @click="toggleNotifications">
        <BellIcon class="icon" />
        <span v-if="unreadCount > 0" class="badge">{{ unreadCount }}</span>
      </button>
      
      <Menu as="div" class="user-menu">
        <MenuButton class="user-button">
          <UserCircleIcon class="icon" />
          <span class="user-name">{{ user?.name || 'User' }}</span>
          <ChevronDownIcon class="icon-sm" />
        </MenuButton>
        
        <transition name="menu">
          <MenuItems class="menu-items">
            <MenuItem v-slot="{ active }">
              <button :class="['menu-item', { active }]" @click="goToProfile">
                <UserIcon class="menu-icon" />
                Profile
              </button>
            </MenuItem>
            <MenuItem v-slot="{ active }">
              <button :class="['menu-item', { active }]" @click="goToSettings">
                <Cog6ToothIcon class="menu-icon" />
                Settings
              </button>
            </MenuItem>
            <MenuItem v-slot="{ active }">
              <button :class="['menu-item', { active }]" @click="handleLogout">
                <ArrowRightOnRectangleIcon class="menu-icon" />
                Logout
              </button>
            </MenuItem>
          </MenuItems>
        </transition>
      </Menu>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import {
  Bars3Icon,
  BellIcon,
  UserCircleIcon,
  ChevronDownIcon,
  UserIcon,
  Cog6ToothIcon,
  ArrowRightOnRectangleIcon
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../stores/authStore'
import { useNotificationStore } from '../../stores/notificationStore'

defineEmits(['toggleMobile'])

const router = useRouter()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()

const user = computed(() => authStore.user)
const unreadCount = computed(() => notificationStore.unreadCount)

const toggleNotifications = () => {
  notificationStore.togglePanel()
}

const goToProfile = () => {
  router.push('/profile')
}

const goToSettings = () => {
  router.push('/settings')
}

const handleLogout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 64px;
  padding: 0 24px;
  background: white;
  border-bottom: 1px solid #e5e7eb;
  position: sticky;
  top: 0;
  z-index: 100;
}

.mobile-menu-btn {
  display: none;
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  color: #4b5563;
}

@media (max-width: 768px) {
  .mobile-menu-btn {
    display: block;
  }
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-left: auto;
}

.icon-btn {
  position: relative;
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  color: #4b5563;
  transition: color 0.2s;
}

.icon-btn:hover {
  color: #1f2937;
}

.icon {
  width: 24px;
  height: 24px;
}

.icon-sm {
  width: 16px;
  height: 16px;
}

.badge {
  position: absolute;
  top: 4px;
  right: 4px;
  background: #ef4444;
  color: white;
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 10px;
  font-weight: 600;
}

.user-menu {
  position: relative;
}

.user-button {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: none;
  border: none;
  cursor: pointer;
  color: #4b5563;
  transition: color 0.2s;
}

.user-button:hover {
  color: #1f2937;
}

.user-name {
  font-size: 14px;
  font-weight: 500;
}

@media (max-width: 640px) {
  .user-name {
    display: none;
  }
}

.menu-items {
  position: absolute;
  right: 0;
  top: calc(100% + 8px);
  width: 200px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  padding: 8px;
  z-index: 200;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 10px 12px;
  background: none;
  border: none;
  cursor: pointer;
  color: #4b5563;
  text-align: left;
  border-radius: 6px;
  transition: all 0.2s;
  font-size: 14px;
}

.menu-item:hover,
.menu-item.active {
  background: #f3f4f6;
  color: #1f2937;
}

.menu-icon {
  width: 18px;
  height: 18px;
}

.menu-enter-active,
.menu-leave-active {
  transition: opacity 0.2s, transform 0.2s;
}

.menu-enter-from,
.menu-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
