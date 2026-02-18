<template>
  <div class="settings-view">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">
        Settings
      </h1>
      <p class="mt-1 text-sm text-gray-600">
        Manage your application settings and preferences
      </p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
      <nav
        class="-mb-px flex space-x-8"
        aria-label="Tabs"
      >
        <button
          v-for="tab in tabs"
          :key="tab.id"
          :class="[
            activeTab === tab.id
              ? 'border-primary-500 text-primary-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
          ]"
          @click="activeTab = tab.id"
        >
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="bg-white shadow rounded-lg p-6">
      <!-- General Settings -->
      <div
        v-if="activeTab === 'general'"
        class="space-y-6"
      >
        <h2 class="text-lg font-medium text-gray-900">
          General Settings
        </h2>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <label class="block text-sm font-medium text-gray-700">Company Name</label>
            <input
              v-model="settings.companyName"
              type="text"
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Time Zone</label>
            <select
              v-model="settings.timezone"
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            >
              <option value="UTC">
                UTC
              </option>
              <option value="America/New_York">
                Eastern Time
              </option>
              <option value="America/Chicago">
                Central Time
              </option>
              <option value="America/Los_Angeles">
                Pacific Time
              </option>
              <option value="Europe/London">
                London
              </option>
              <option value="Asia/Tokyo">
                Tokyo
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Date Format</label>
            <select
              v-model="settings.dateFormat"
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            >
              <option value="MM/DD/YYYY">
                MM/DD/YYYY
              </option>
              <option value="DD/MM/YYYY">
                DD/MM/YYYY
              </option>
              <option value="YYYY-MM-DD">
                YYYY-MM-DD
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Currency</label>
            <select
              v-model="settings.currency"
              class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            >
              <option value="USD">
                USD - US Dollar
              </option>
              <option value="EUR">
                EUR - Euro
              </option>
              <option value="GBP">
                GBP - British Pound
              </option>
              <option value="JPY">
                JPY - Japanese Yen
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Notification Settings -->
      <div
        v-if="activeTab === 'notifications'"
        class="space-y-6"
      >
        <h2 class="text-lg font-medium text-gray-900">
          Notification Preferences
        </h2>
        
        <div class="space-y-4">
          <div class="flex items-start">
            <div class="flex items-center h-5">
              <input
                v-model="settings.emailNotifications"
                type="checkbox"
                class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded"
              >
            </div>
            <div class="ml-3 text-sm">
              <label class="font-medium text-gray-700">Email Notifications</label>
              <p class="text-gray-500">
                Receive email notifications for important events
              </p>
            </div>
          </div>

          <div class="flex items-start">
            <div class="flex items-center h-5">
              <input
                v-model="settings.pushNotifications"
                type="checkbox"
                class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded"
              >
            </div>
            <div class="ml-3 text-sm">
              <label class="font-medium text-gray-700">Push Notifications</label>
              <p class="text-gray-500">
                Receive push notifications in your browser
              </p>
            </div>
          </div>

          <div class="flex items-start">
            <div class="flex items-center h-5">
              <input
                v-model="settings.smsNotifications"
                type="checkbox"
                class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded"
              >
            </div>
            <div class="ml-3 text-sm">
              <label class="font-medium text-gray-700">SMS Notifications</label>
              <p class="text-gray-500">
                Receive SMS notifications for critical alerts
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Security Settings -->
      <div
        v-if="activeTab === 'security'"
        class="space-y-6"
      >
        <h2 class="text-lg font-medium text-gray-900">
          Security Settings
        </h2>
        
        <div class="space-y-4">
          <div class="flex items-start">
            <div class="flex items-center h-5">
              <input
                v-model="settings.twoFactorAuth"
                type="checkbox"
                class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded"
              >
            </div>
            <div class="ml-3 text-sm">
              <label class="font-medium text-gray-700">Two-Factor Authentication</label>
              <p class="text-gray-500">
                Add an extra layer of security to your account
              </p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Session Timeout</label>
            <select
              v-model="settings.sessionTimeout"
              class="block w-full max-w-xs border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            >
              <option value="15">
                15 minutes
              </option>
              <option value="30">
                30 minutes
              </option>
              <option value="60">
                1 hour
              </option>
              <option value="120">
                2 hours
              </option>
              <option value="240">
                4 hours
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="mt-6 pt-6 border-t border-gray-200 flex justify-end">
        <button
          :disabled="saving"
          class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
          @click="saveSettings"
        >
          {{ saving ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';

const activeTab = ref('general');
const saving = ref(false);

const tabs = [
  { id: 'general', name: 'General' },
  { id: 'notifications', name: 'Notifications' },
  { id: 'security', name: 'Security' },
];

const settings = reactive({
  companyName: 'AutoERP',
  timezone: 'UTC',
  dateFormat: 'YYYY-MM-DD',
  currency: 'USD',
  emailNotifications: true,
  pushNotifications: true,
  smsNotifications: false,
  twoFactorAuth: false,
  sessionTimeout: '30',
});

const saveSettings = async () => {
  saving.value = true;
  
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    console.log('Settings saved successfully');
  } catch (error) {
    console.error('Failed to save settings', error);
  } finally {
    saving.value = false;
  }
};
</script>
