<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-xl font-bold text-indigo-600">ModularSaaS</h1>
          </div>
          <div class="flex items-center space-x-4">
            <RouterLink
              v-if="!authStore.isAuthenticated"
              to="/login"
              class="text-gray-700 hover:text-indigo-600 px-3 py-2 text-sm font-medium"
            >
              {{ $t('auth.login') }}
            </RouterLink>
            <RouterLink
              v-if="!authStore.isAuthenticated"
              to="/register"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
            >
              {{ $t('auth.register') }}
            </RouterLink>
            <RouterLink
              v-if="authStore.isAuthenticated"
              to="/dashboard"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
            >
              {{ $t('dashboard.title') }}
            </RouterLink>
          </div>
        </div>
      </div>
    </nav>

    <main class="flex-grow">
      <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
        <div class="text-center">
          <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
            <span class="block">Welcome to</span>
            <span class="block text-indigo-600">ModularSaaS</span>
          </h1>
          <p class="mt-3 max-w-md mx-auto text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
            A production-ready, enterprise-grade modular SaaS application built with Laravel 11 and Vue.js 3.
          </p>
          <div class="mt-5 max-w-md mx-auto sm:flex sm:justify-center md:mt-8">
            <div class="rounded-md shadow">
              <RouterLink
                v-if="!authStore.isAuthenticated"
                to="/register"
                class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 md:py-4 md:text-lg md:px-10"
              >
                Get started
              </RouterLink>
              <RouterLink
                v-else
                to="/dashboard"
                class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 md:py-4 md:text-lg md:px-10"
              >
                Go to Dashboard
              </RouterLink>
            </div>
            <div class="mt-3 rounded-md shadow sm:mt-0 sm:ml-3">
              <a
                href="https://github.com/kasunvimarshana/ModularSaaS-LaravelVue"
                target="_blank"
                class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-indigo-600 bg-white hover:bg-gray-50 md:py-4 md:text-lg md:px-10"
              >
                View on GitHub
              </a>
            </div>
          </div>
        </div>

        <div class="mt-16">
          <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
            <div class="pt-6">
              <div class="flow-root bg-white rounded-lg px-6 pb-8">
                <div class="-mt-6">
                  <div>
                    <span class="inline-flex items-center justify-center p-3 bg-indigo-500 rounded-md shadow-lg">
                      <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                      </svg>
                    </span>
                  </div>
                  <h3 class="mt-8 text-lg font-medium text-gray-900 tracking-tight">Secure Authentication</h3>
                  <p class="mt-5 text-base text-gray-500">
                    Enterprise-grade authentication with Laravel Sanctum, RBAC, and comprehensive audit logging.
                  </p>
                </div>
              </div>
            </div>

            <div class="pt-6">
              <div class="flow-root bg-white rounded-lg px-6 pb-8">
                <div class="-mt-6">
                  <div>
                    <span class="inline-flex items-center justify-center p-3 bg-indigo-500 rounded-md shadow-lg">
                      <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                      </svg>
                    </span>
                  </div>
                  <h3 class="mt-8 text-lg font-medium text-gray-900 tracking-tight">Modular Architecture</h3>
                  <p class="mt-5 text-base text-gray-500">
                    Clean, maintainable code with strict Controller → Service → Repository pattern.
                  </p>
                </div>
              </div>
            </div>

            <div class="pt-6">
              <div class="flow-root bg-white rounded-lg px-6 pb-8">
                <div class="-mt-6">
                  <div>
                    <span class="inline-flex items-center justify-center p-3 bg-indigo-500 rounded-md shadow-lg">
                      <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                      </svg>
                    </span>
                  </div>
                  <h3 class="mt-8 text-lg font-medium text-gray-900 tracking-tight">Multi-Tenancy</h3>
                  <p class="mt-5 text-base text-gray-500">
                    Built-in multi-tenancy support with complete data isolation per tenant.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer class="bg-white">
      <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 md:flex md:items-center md:justify-between lg:px-8">
        <div class="mt-8 md:mt-0 md:order-1">
          <p class="text-center text-base text-gray-400">
            &copy; 2024 ModularSaaS. Built with Laravel & Vue.js.
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';

const authStore = useAuthStore();
const { t } = useI18n();
</script>
