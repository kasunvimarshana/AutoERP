<template>
  <div class="notification-settings">
    <div class="settings-header">
      <h2 class="text-2xl font-bold text-gray-900">Notification Settings</h2>
      <p class="text-sm text-gray-600 mt-1">
        Manage how you receive notifications from AutoERP
      </p>
    </div>
    
    <!-- Push Notifications -->
    <div class="settings-section">
      <div class="section-header">
        <BellIcon class="icon text-blue-600" />
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Push Notifications</h3>
          <p class="text-sm text-gray-600">Receive real-time notifications in your browser</p>
        </div>
      </div>
      
      <div class="section-content">
        <!-- Not supported -->
        <div v-if="!isSupported" class="alert alert-warning">
          <ExclamationTriangleIcon class="icon" />
          <div>
            <p class="font-semibold">Push notifications not supported</p>
            <p class="text-sm">Your browser doesn't support push notifications</p>
          </div>
        </div>
        
        <!-- Blocked -->
        <div v-else-if="isBlocked" class="alert alert-error">
          <XCircleIcon class="icon" />
          <div>
            <p class="font-semibold">Notifications blocked</p>
            <p class="text-sm">You have blocked notifications. Please enable them in your browser settings.</p>
          </div>
        </div>
        
        <!-- Enable/Disable -->
        <div v-else class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
          <div class="flex items-center space-x-3">
            <div 
              :class="[
                'status-indicator',
                isSubscribed ? 'status-active' : 'status-inactive'
              ]"
            />
            <div>
              <p class="font-medium text-gray-900">
                {{ isSubscribed ? 'Enabled' : 'Disabled' }}
              </p>
              <p class="text-sm text-gray-600">
                {{ isSubscribed ? 'You will receive push notifications' : 'Enable to receive notifications' }}
              </p>
            </div>
          </div>
          
          <button
            @click="togglePushNotifications"
            :disabled="isLoading"
            :class="[
              'btn',
              isSubscribed ? 'btn-secondary' : 'btn-primary'
            ]"
          >
            <LoadingSpinner v-if="isLoading" class="icon" />
            <template v-else>
              {{ isSubscribed ? 'Disable' : 'Enable' }}
            </template>
          </button>
        </div>
        
        <!-- Test notification -->
        <button
          v-if="isSubscribed"
          @click="sendTest"
          :disabled="isLoading"
          class="btn btn-outline mt-3"
        >
          <PaperAirplaneIcon class="icon" />
          Send Test Notification
        </button>
      </div>
    </div>
    
    <!-- Notification Preferences -->
    <div class="settings-section">
      <div class="section-header">
        <AdjustmentsHorizontalIcon class="icon text-purple-600" />
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Notification Preferences</h3>
          <p class="text-sm text-gray-600">Choose which notifications you want to receive</p>
        </div>
      </div>
      
      <div class="section-content">
        <LoadingSpinner v-if="loadingPreferences" />
        
        <div v-else class="space-y-4">
          <div
            v-for="pref in preferences"
            :key="pref.event_type"
            class="preference-item"
          >
            <div class="flex-1">
              <p class="font-medium text-gray-900">{{ formatEventType(pref.event_type) }}</p>
              <p class="text-sm text-gray-600">{{ getEventDescription(pref.event_type) }}</p>
            </div>
            
            <div class="flex items-center space-x-6">
              <!-- Web Push -->
              <label class="flex items-center space-x-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="pref.channels.web_push"
                  @change="updatePreference(pref.event_type, 'web_push', $event.target.checked)"
                  class="checkbox"
                />
                <span class="text-sm text-gray-700">Push</span>
              </label>
              
              <!-- Email -->
              <label class="flex items-center space-x-2 cursor-pointer">
                <input
                  type="checkbox"
                  :checked="pref.channels.email"
                  @change="updatePreference(pref.event_type, 'email', $event.target.checked)"
                  class="checkbox"
                />
                <span class="text-sm text-gray-700">Email</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Error display -->
    <div v-if="error" class="alert alert-error mt-4">
      <XCircleIcon class="icon" />
      <p>{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePushNotifications } from '@/composables/usePushNotifications'
import apiClient from '@/services/api'
import {
  BellIcon,
  AdjustmentsHorizontalIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  PaperAirplaneIcon
} from '@heroicons/vue/24/outline'
import LoadingSpinner from '@/components/common/LoadingSpinner.vue'

const {
  isSupported,
  isSubscribed,
  isLoading,
  isBlocked,
  error: pushError,
  enableNotifications,
  unsubscribe,
  sendTestNotification
} = usePushNotifications()

const preferences = ref([])
const loadingPreferences = ref(false)
const error = ref(null)

// Event type descriptions
const eventDescriptions = {
  'stock.low': 'When product stock falls below reorder level',
  'stock.out': 'When product is out of stock',
  'order.created': 'When a new sales order is created',
  'order.confirmed': 'When a sales order is confirmed',
  'invoice.created': 'When a new invoice is generated',
  'invoice.paid': 'When an invoice is marked as paid',
  'purchase.approved': 'When a purchase order is approved',
  'user.assigned': 'When you are assigned to a task',
  'system.update': 'System updates and announcements'
}

// Format event type for display
const formatEventType = (eventType) => {
  return eventType.split('.').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

// Get event description
const getEventDescription = (eventType) => {
  return eventDescriptions[eventType] || 'Notification for this event'
}

// Toggle push notifications
const togglePushNotifications = async () => {
  try {
    error.value = null
    
    if (isSubscribed.value) {
      await unsubscribe()
    } else {
      await enableNotifications()
    }
  } catch (err) {
    error.value = pushError.value || err.message
  }
}

// Send test notification
const sendTest = async () => {
  try {
    error.value = null
    await sendTestNotification()
  } catch (err) {
    error.value = pushError.value || err.message
  }
}

// Fetch notification preferences
const fetchPreferences = async () => {
  loadingPreferences.value = true
  
  try {
    const response = await apiClient.get('/notifications/preferences')
    
    // Group by event type
    const grouped = {}
    response.data.forEach(pref => {
      if (!grouped[pref.event_type]) {
        grouped[pref.event_type] = {
          event_type: pref.event_type,
          channels: {}
        }
      }
      grouped[pref.event_type].channels[pref.channel] = pref.enabled
    })
    
    preferences.value = Object.values(grouped)
  } catch (err) {
    console.error('Error fetching preferences:', err)
    error.value = 'Failed to load notification preferences'
  } finally {
    loadingPreferences.value = false
  }
}

// Update notification preference
const updatePreference = async (eventType, channel, enabled) => {
  try {
    await apiClient.put('/notifications/preferences', {
      event_type: eventType,
      channel,
      enabled
    })
    
    // Update local state
    const pref = preferences.value.find(p => p.event_type === eventType)
    if (pref) {
      pref.channels[channel] = enabled
    }
  } catch (err) {
    console.error('Error updating preference:', err)
    error.value = 'Failed to update notification preference'
    
    // Reload preferences to sync state
    await fetchPreferences()
  }
}

// Initialize
onMounted(() => {
  fetchPreferences()
})
</script>

<style scoped>
.notification-settings {
  @apply max-w-4xl mx-auto p-6 space-y-8;
}

.settings-header {
  @apply pb-6 border-b border-gray-200;
}

.settings-section {
  @apply bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden;
}

.section-header {
  @apply flex items-start space-x-3 p-6 bg-gray-50 border-b border-gray-200;
}

.section-header .icon {
  @apply w-6 h-6 flex-shrink-0 mt-1;
}

.section-content {
  @apply p-6;
}

.preference-item {
  @apply flex items-center justify-between p-4 bg-gray-50 rounded-lg;
}

.status-indicator {
  @apply w-3 h-3 rounded-full;
}

.status-active {
  @apply bg-green-500;
}

.status-inactive {
  @apply bg-gray-300;
}

.alert {
  @apply flex items-start space-x-3 p-4 rounded-lg;
}

.alert .icon {
  @apply w-5 h-5 flex-shrink-0 mt-0.5;
}

.alert-warning {
  @apply bg-yellow-50 text-yellow-900 border border-yellow-200;
}

.alert-error {
  @apply bg-red-50 text-red-900 border border-red-200;
}

.btn {
  @apply px-4 py-2 rounded-lg font-medium transition-colors duration-200;
}

.btn .icon {
  @apply w-4 h-4 mr-2 inline;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.btn-secondary {
  @apply bg-gray-600 text-white hover:bg-gray-700;
}

.btn-outline {
  @apply bg-white text-gray-700 border border-gray-300 hover:bg-gray-50;
}

.btn:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.checkbox {
  @apply w-4 h-4 text-blue-600 rounded focus:ring-blue-500;
}
</style>
