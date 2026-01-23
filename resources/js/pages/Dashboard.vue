<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <h1 class="text-xl font-bold text-indigo-600">ModularSaaS</h1>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <RouterLink
                to="/dashboard"
                class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
              >
                {{ $t('dashboard.title') }}
              </RouterLink>
              <RouterLink
                to="/profile"
                class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
              >
                {{ $t('profile.title') }}
              </RouterLink>
            </div>
          </div>
          <div class="hidden sm:ml-6 sm:flex sm:items-center">
            <div class="ml-3 relative">
              <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-700">{{ authStore.userName }}</span>
                <button
                  @click="handleLogout"
                  class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  {{ $t('auth.logout') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <div class="py-10">
      <header>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 class="text-3xl font-bold leading-tight text-gray-900">
            {{ $t('dashboard.title') }}
          </h1>
        </div>
      </header>
      <main>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="px-4 py-8 sm:px-0">
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
              <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                  {{ $t('dashboard.welcome') }}, {{ authStore.userName }}!
                </h3>
                <div class="mt-5">
                  <dl class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="px-4 py-5 bg-gray-50 shadow rounded-lg overflow-hidden sm:p-6">
                      <dt class="text-sm font-medium text-gray-500 truncate">
                        Email
                      </dt>
                      <dd class="mt-1 text-3xl font-semibold text-gray-900">
                        {{ authStore.userEmail }}
                      </dd>
                    </div>
                    <div class="px-4 py-5 bg-gray-50 shadow rounded-lg overflow-hidden sm:p-6">
                      <dt class="text-sm font-medium text-gray-500 truncate">
                        Roles
                      </dt>
                      <dd class="mt-1 text-lg font-semibold text-gray-900">
                        <span
                          v-for="role in authStore.userRoles"
                          :key="role"
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 mr-1"
                        >
                          {{ role }}
                        </span>
                      </dd>
                    </div>
                    <div class="px-4 py-5 bg-gray-50 shadow rounded-lg overflow-hidden sm:p-6">
                      <dt class="text-sm font-medium text-gray-500 truncate">
                        Permissions
                      </dt>
                      <dd class="mt-1 text-sm font-semibold text-gray-900">
                        {{ authStore.userPermissions.length }} permissions
                      </dd>
                    </div>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';

const router = useRouter();
const authStore = useAuthStore();
const { t } = useI18n();

const handleLogout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>
