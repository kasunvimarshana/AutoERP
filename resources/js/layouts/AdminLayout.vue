<template>
  <div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
      <!-- Left navbar links -->
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button" @click.prevent="toggleSidebar">
            <i class="fas fa-bars"></i>
          </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
          <RouterLink to="/dashboard" class="nav-link">
            {{ $t('dashboard.title') }}
          </RouterLink>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
          <RouterLink to="/profile" class="nav-link">
            {{ $t('profile.title') }}
          </RouterLink>
        </li>
      </ul>

      <!-- Right navbar links -->
      <ul class="navbar-nav ml-auto">
        <!-- Language Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link" data-toggle="dropdown" href="#" @click.prevent="toggleLanguageDropdown">
            <i class="fas fa-globe"></i>
            {{ currentLocale.toUpperCase() }}
          </a>
          <div class="dropdown-menu dropdown-menu-right" :class="{ show: showLanguageDropdown }">
            <a href="#" class="dropdown-item" @click.prevent="changeLanguage('en')">
              <i class="flag-icon flag-icon-us mr-2"></i> English
            </a>
            <a href="#" class="dropdown-item" @click.prevent="changeLanguage('es')">
              <i class="flag-icon flag-icon-es mr-2"></i> Español
            </a>
            <a href="#" class="dropdown-item" @click.prevent="changeLanguage('fr')">
              <i class="flag-icon flag-icon-fr mr-2"></i> Français
            </a>
          </div>
        </li>

        <!-- User Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link" data-toggle="dropdown" href="#" @click.prevent="toggleUserDropdown">
            <i class="fas fa-user-circle"></i>
            {{ authStore.userName }}
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" :class="{ show: showUserDropdown }">
            <span class="dropdown-item dropdown-header">{{ authStore.userEmail }}</span>
            <div class="dropdown-divider"></div>
            <RouterLink to="/profile" class="dropdown-item" @click="closeDropdowns">
              <i class="fas fa-user mr-2"></i> {{ $t('profile.title') }}
            </RouterLink>
            <div class="dropdown-divider"></div>
            <a href="#" class="dropdown-item" @click.prevent="handleLogout">
              <i class="fas fa-sign-out-alt mr-2"></i> {{ $t('auth.logout') }}
            </a>
          </div>
        </li>
      </ul>
    </nav>

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
      <!-- Brand Logo -->
      <RouterLink to="/dashboard" class="brand-link">
        <span class="brand-text font-weight-light">ModularSaaS</span>
      </RouterLink>

      <!-- Sidebar -->
      <div class="sidebar">
        <!-- Sidebar user panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
          <div class="image">
            <div class="img-circle elevation-2 bg-white d-flex align-items-center justify-content-center user-avatar-placeholder">
              <i class="fas fa-user text-secondary"></i>
            </div>
          </div>
          <div class="info">
            <a href="#" class="d-block">{{ authStore.userName }}</a>
          </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
            <li class="nav-item">
              <RouterLink to="/dashboard" class="nav-link" :class="{ active: $route.path === '/dashboard' }">
                <i class="nav-icon fas fa-tachometer-alt"></i>
                <p>{{ $t('dashboard.title') }}</p>
              </RouterLink>
            </li>
            <li class="nav-item">
              <RouterLink to="/profile" class="nav-link" :class="{ active: $route.path === '/profile' }">
                <i class="nav-icon fas fa-user"></i>
                <p>{{ $t('profile.title') }}</p>
              </RouterLink>
            </li>
            <li class="nav-header" role="heading" aria-level="3">{{ $t('common.settings') || 'SETTINGS' }}</li>
            <li class="nav-item">
              <a href="#" class="nav-link" @click.prevent="handleLogout">
                <i class="nav-icon fas fa-sign-out-alt"></i>
                <p>{{ $t('auth.logout') }}</p>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
      <!-- Content Header -->
      <div class="content-header" v-if="pageTitle">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">{{ pageTitle }}</h1>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><RouterLink to="/dashboard">{{ $t('common.home') || 'Home' }}</RouterLink></li>
                <li class="breadcrumb-item active">{{ pageTitle }}</li>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <!-- Main content -->
      <div class="content">
        <div class="container-fluid">
          <slot></slot>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
      <strong>Copyright &copy; 2024 <a href="#">ModularSaaS</a>.</strong>
      All rights reserved.
      <div class="float-right d-none d-sm-inline-block">
        <b>Version</b> 1.0.0
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';

const props = defineProps({
  pageTitle: {
    type: String,
    default: ''
  }
});

const router = useRouter();
const authStore = useAuthStore();
const { t, locale } = useI18n();

const showLanguageDropdown = ref(false);
const showUserDropdown = ref(false);
const sidebarCollapsed = ref(false);

const currentLocale = computed(() => locale.value);

const toggleSidebar = () => {
  sidebarCollapsed.value = !sidebarCollapsed.value;
  document.body.classList.toggle('sidebar-collapse');
};

const toggleLanguageDropdown = () => {
  showLanguageDropdown.value = !showLanguageDropdown.value;
  if (showLanguageDropdown.value) {
    showUserDropdown.value = false;
  }
};

const toggleUserDropdown = () => {
  showUserDropdown.value = !showUserDropdown.value;
  if (showUserDropdown.value) {
    showLanguageDropdown.value = false;
  }
};

const closeDropdowns = () => {
  showLanguageDropdown.value = false;
  showUserDropdown.value = false;
};

const changeLanguage = (lang) => {
  locale.value = lang;
  localStorage.setItem('locale', lang);
  closeDropdowns();
};

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>

<style scoped>
/* Additional custom styles if needed */
.dropdown-menu.show {
  display: block;
}

/* User avatar placeholder */
.user-avatar-placeholder {
  width: 34px;
  height: 34px;
}
</style>
