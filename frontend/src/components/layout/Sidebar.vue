<template>
  <aside :class="['sidebar', { 'mobile-open': isMobileOpen }]">
    <div class="sidebar-header">
      <h1 class="logo">AutoERP</h1>
    </div>
    
    <nav class="sidebar-nav">
      <template v-for="item in navigationItems" :key="item.name">
        <div v-if="!item.children">
          <router-link :to="item.path" class="nav-item">
            <component :is="item.icon" class="nav-icon" />
            <span>{{ item.name }}</span>
          </router-link>
        </div>
        <div v-else>
          <button class="nav-item" @click="toggleSubmenu(item)">
            <component :is="item.icon" class="nav-icon" />
            <span>{{ item.name }}</span>
            <ChevronDownIcon class="chevron" :class="{ 'open': item.isOpen }" />
          </button>
          <div v-if="item.isOpen" class="submenu">
            <router-link 
              v-for="child in item.children"
              :key="child.name"
              :to="child.path"
              class="submenu-item"
            >
              {{ child.name }}
            </router-link>
          </div>
        </div>
      </template>
    </nav>
    
    <div v-if="isMobileOpen" class="sidebar-overlay" @click="$emit('close')"></div>
  </aside>
</template>

<script setup>
import { ref } from 'vue'
import {
  HomeIcon,
  UserGroupIcon,
  CubeIcon,
  UsersIcon,
  ShoppingCartIcon,
  ClipboardDocumentListIcon,
  CogIcon,
  CurrencyDollarIcon,
  ChartBarIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'

defineProps({
  isMobileOpen: Boolean
})

defineEmits(['close'])

const navigationItems = ref([
  { name: 'Dashboard', path: '/dashboard', icon: HomeIcon },
  { 
    name: 'IAM', 
    icon: UserGroupIcon,
    children: [
      { name: 'Users', path: '/iam/users' },
      { name: 'Roles', path: '/iam/roles' },
      { name: 'Permissions', path: '/iam/permissions' }
    ],
    isOpen: false
  },
  { 
    name: 'Inventory', 
    icon: CubeIcon,
    children: [
      { name: 'Products', path: '/inventory/products' },
      { name: 'Stock Ledgers', path: '/inventory/stock-ledgers' },
      { name: 'Stock Movements', path: '/inventory/stock-movements' },
      { name: 'Warehouses', path: '/inventory/warehouses' }
    ],
    isOpen: false
  },
  { 
    name: 'CRM', 
    icon: UsersIcon,
    children: [
      { name: 'Customers', path: '/crm/customers' },
      { name: 'Contacts', path: '/crm/contacts' }
    ],
    isOpen: false
  },
  { 
    name: 'POS', 
    icon: ShoppingCartIcon,
    children: [
      { name: 'Quotations', path: '/pos/quotations' },
      { name: 'Sales Orders', path: '/pos/sales-orders' },
      { name: 'Invoices', path: '/pos/invoices' },
      { name: 'Payments', path: '/pos/payments' }
    ],
    isOpen: false
  },
  { 
    name: 'Procurement', 
    icon: ClipboardDocumentListIcon,
    children: [
      { name: 'Suppliers', path: '/procurement/suppliers' },
      { name: 'Purchase Orders', path: '/procurement/purchase-orders' },
      { name: 'GRNs', path: '/procurement/grns' }
    ],
    isOpen: false
  },
  { 
    name: 'Manufacturing', 
    icon: CogIcon,
    children: [
      { name: 'BOMs', path: '/manufacturing/boms' },
      { name: 'Production Orders', path: '/manufacturing/production-orders' },
      { name: 'Work Orders', path: '/manufacturing/work-orders' }
    ],
    isOpen: false
  },
  { 
    name: 'Finance', 
    icon: CurrencyDollarIcon,
    children: [
      { name: 'Accounts', path: '/finance/accounts' },
      { name: 'Journal Entries', path: '/finance/journal-entries' },
      { name: 'Reports', path: '/finance/reports' }
    ],
    isOpen: false
  },
  { 
    name: 'Reporting', 
    icon: ChartBarIcon,
    children: [
      { name: 'Dashboards', path: '/reporting/dashboards' },
      { name: 'Analytics', path: '/reporting/analytics' }
    ],
    isOpen: false
  }
])

const toggleSubmenu = (item) => {
  item.isOpen = !item.isOpen
}
</script>

<style scoped>
.sidebar {
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  width: 250px;
  background: #1f2937;
  color: white;
  overflow-y: auto;
  z-index: 1000;
  transition: transform 0.3s ease;
}

@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }
  
  .sidebar.mobile-open {
    transform: translateX(0);
  }
  
  .sidebar-overlay {
    position: fixed;
    top: 0;
    left: 250px;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
  }
}

.sidebar-header {
  padding: 20px;
  border-bottom: 1px solid #374151;
}

.logo {
  font-size: 20px;
  font-weight: bold;
  color: #3b82f6;
}

.sidebar-nav {
  padding: 16px 0;
}

.nav-item {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 12px 20px;
  color: #d1d5db;
  text-decoration: none;
  transition: all 0.2s;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
}

.nav-item:hover {
  background: #374151;
  color: white;
}

.nav-item.router-link-active {
  background: #374151;
  color: #3b82f6;
  border-left: 3px solid #3b82f6;
}

.nav-icon {
  width: 20px;
  height: 20px;
  margin-right: 12px;
  flex-shrink: 0;
}

.chevron {
  width: 16px;
  height: 16px;
  margin-left: auto;
  transition: transform 0.2s;
}

.chevron.open {
  transform: rotate(180deg);
}

.submenu {
  background: #111827;
}

.submenu-item {
  display: block;
  padding: 10px 20px 10px 52px;
  color: #9ca3af;
  text-decoration: none;
  font-size: 14px;
  transition: all 0.2s;
}

.submenu-item:hover {
  background: #1f2937;
  color: white;
}

.submenu-item.router-link-active {
  color: #3b82f6;
  background: #1f2937;
}
</style>
