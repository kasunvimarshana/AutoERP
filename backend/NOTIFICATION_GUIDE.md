# Native Push Notifications Guide - AutoERP

## Overview

AutoERP implements **native browser push notifications** using the Web Push API with VAPID (Voluntary Application Server Identification) for authentication. **No third-party services** like Firebase, OneSignal, or Pusher are required.

## Architecture

### Components

1. **Backend**:
   - `WebPushService`: Handles push notification sending
   - `NotificationService`: Manages user notifications
   - `PushSubscription` model: Stores browser subscriptions
   - API endpoints for subscription management
   - VAPID keys for authentication

2. **Frontend**:
   - Service Worker: Receives and displays notifications
   - Subscription management UI
   - Permission requests
   - Notification preferences

3. **Database**:
   - `push_subscriptions`: Stores subscription endpoints
   - `notifications`: Stores notification history
   - `users.notification_preferences`: User notification settings

## Setup

### 1. Install Required Package

The `web-push-php` library is already included in `composer.json`:

```bash
composer require minishlink/web-push
```

### 2. Generate VAPID Keys

Run the artisan command to generate VAPID keys:

```bash
php artisan webpush:generate-keys
```

This will output:

```
VAPID_PUBLIC_KEY=BKxFh...
VAPID_PRIVATE_KEY=aAb3c...
VAPID_SUBJECT=mailto:admin@autoerp.com
```

### 3. Add to Environment

Add the generated keys to your `.env` file:

```env
VAPID_PUBLIC_KEY=your-generated-public-key
VAPID_PRIVATE_KEY=your-generated-private-key
VAPID_SUBJECT=mailto:admin@autoerp.com
```

⚠️ **IMPORTANT**: Keep the private key secure. Never commit it to version control!

### 4. Run Migrations

Run migrations to create required tables:

```bash
php artisan migrate
```

This creates:
- `push_subscriptions` table
- Adds `notification_preferences` column to `users` table

### 5. Configure Queue Workers

Push notifications are sent asynchronously via queues. Ensure queue workers are running:

```bash
php artisan queue:work
```

For production, use Supervisor (see EVENT_SYSTEM.md).

## Frontend Integration

### 1. Register Service Worker

In your Vue.js app entry point (`main.js` or `App.vue`):

```javascript
// Register service worker
if ('serviceWorker' in navigator) {
  navigator.serviceWorker
    .register('/js/service-worker.js')
    .then((registration) => {
      console.log('Service Worker registered:', registration);
    })
    .catch((error) => {
      console.error('Service Worker registration failed:', error);
    });
}
```

### 2. Request Permission

Create a utility function to request notification permission:

```javascript
// utils/notifications.js
export async function requestNotificationPermission() {
  if (!('Notification' in window)) {
    console.warn('This browser does not support notifications');
    return false;
  }

  if (Notification.permission === 'granted') {
    return true;
  }

  if (Notification.permission !== 'denied') {
    const permission = await Notification.requestPermission();
    return permission === 'granted';
  }

  return false;
}
```

### 3. Subscribe to Push Notifications

```javascript
// utils/notifications.js
import axios from 'axios';

export async function subscribeToPush() {
  try {
    // Get service worker registration
    const registration = await navigator.serviceWorker.ready;

    // Get VAPID public key from backend
    const { data } = await axios.get('/api/v1/push/public-key');
    const publicKey = data.data.public_key;

    // Convert VAPID key to Uint8Array
    const convertedKey = urlBase64ToUint8Array(publicKey);

    // Subscribe to push notifications
    const subscription = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: convertedKey,
    });

    // Send subscription to backend
    await axios.post('/api/v1/push/subscribe', subscription.toJSON());

    console.log('Successfully subscribed to push notifications');
    return true;
  } catch (error) {
    console.error('Failed to subscribe to push notifications:', error);
    return false;
  }
}

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding)
    .replace(/\-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; i++) {
    outputArray[i] = rawData.charCodeAt(i);
  }

  return outputArray;
}
```

### 4. Complete Notification Setup

```javascript
// Complete setup function
export async function setupNotifications() {
  // Check if notifications are supported
  if (!('Notification' in window) || !('serviceWorker' in navigator)) {
    console.warn('Push notifications not supported');
    return false;
  }

  // Request permission
  const hasPermission = await requestNotificationPermission();
  if (!hasPermission) {
    console.warn('Notification permission denied');
    return false;
  }

  // Subscribe to push
  const subscribed = await subscribeToPush();
  return subscribed;
}
```

### 5. Vue Component Example

```vue
<template>
  <div class="notification-settings">
    <h3>Notification Settings</h3>
    
    <div v-if="!isSupported" class="alert alert-warning">
      Your browser does not support push notifications.
    </div>
    
    <div v-else>
      <div v-if="permission === 'denied'" class="alert alert-danger">
        You have blocked notifications. Please enable them in your browser settings.
      </div>
      
      <div v-else-if="!isSubscribed">
        <button @click="enableNotifications" class="btn btn-primary">
          Enable Push Notifications
        </button>
      </div>
      
      <div v-else class="alert alert-success">
        Push notifications are enabled!
        <button @click="disableNotifications" class="btn btn-secondary btn-sm">
          Disable
        </button>
        <button @click="testNotification" class="btn btn-secondary btn-sm">
          Test
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { setupNotifications } from '@/utils/notifications';
import axios from 'axios';

export default {
  name: 'NotificationSettings',
  
  data() {
    return {
      isSupported: false,
      permission: null,
      isSubscribed: false,
    };
  },
  
  mounted() {
    this.checkSupport();
    this.checkPermission();
    this.checkSubscription();
  },
  
  methods: {
    checkSupport() {
      this.isSupported = 'Notification' in window && 'serviceWorker' in navigator;
    },
    
    checkPermission() {
      if (this.isSupported) {
        this.permission = Notification.permission;
      }
    },
    
    async checkSubscription() {
      if (!this.isSupported) return;
      
      try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        this.isSubscribed = subscription !== null;
      } catch (error) {
        console.error('Error checking subscription:', error);
      }
    },
    
    async enableNotifications() {
      const success = await setupNotifications();
      if (success) {
        this.isSubscribed = true;
        this.permission = 'granted';
        this.$toast.success('Push notifications enabled!');
      } else {
        this.$toast.error('Failed to enable push notifications');
      }
    },
    
    async disableNotifications() {
      try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        
        if (subscription) {
          await axios.post('/api/v1/push/unsubscribe', {
            endpoint: subscription.endpoint,
          });
          
          await subscription.unsubscribe();
          this.isSubscribed = false;
          this.$toast.success('Push notifications disabled');
        }
      } catch (error) {
        console.error('Error disabling notifications:', error);
        this.$toast.error('Failed to disable notifications');
      }
    },
    
    async testNotification() {
      try {
        await axios.post('/api/v1/push/test');
        this.$toast.success('Test notification sent!');
      } catch (error) {
        console.error('Error sending test notification:', error);
        this.$toast.error('Failed to send test notification');
      }
    },
  },
};
</script>
```

## Backend Usage

### Sending Notifications via Events

Notifications are automatically sent when domain events are dispatched:

```php
use App\Events\Sales\OrderCreated;

// Dispatch event - notifications sent automatically
OrderCreated::dispatch(
    orderId: $order->id,
    orderNumber: $order->order_number,
    customerId: $order->customer_id,
    customerName: $order->customer->name,
    totalAmount: $order->total_amount,
    tenantId: $tenant->id,
    userId: auth()->id()
);
```

### Manual Notification Sending

```php
use App\Services\Notification\NotificationService;

$notificationService = app(NotificationService::class);

$notificationService->sendBulkNotification(
    userIds: [1, 2, 3],
    title: 'System Maintenance',
    message: 'The system will be under maintenance from 10 PM to 12 AM',
    type: 'warning',
    actionUrl: '/announcements/123'
);
```

### Sending Push Directly

```php
use App\Services\WebPush\WebPushService;

$webPushService = app(WebPushService::class);

$webPushService->sendToUser(
    user: $user,
    payload: [
        'title' => 'New Message',
        'body' => 'You have a new message from Admin',
        'icon' => '/images/message-icon.png',
        'badge' => '/images/badge.png',
        'tag' => 'new-message',
        'data' => [
            'url' => '/messages/123',
            'message_id' => 123,
        ],
    ]
);
```

## API Endpoints

### Get VAPID Public Key

```http
GET /api/v1/push/public-key
```

Response:
```json
{
  "success": true,
  "data": {
    "public_key": "BKxFh..."
  }
}
```

### Subscribe to Push Notifications

```http
POST /api/v1/push/subscribe
Authorization: Bearer {token}
Content-Type: application/json

{
  "endpoint": "https://fcm.googleapis.com/fcm/send/...",
  "keys": {
    "p256dh": "BGd...",
    "auth": "AbC..."
  },
  "contentEncoding": "aes128gcm"
}
```

### Unsubscribe

```http
POST /api/v1/push/unsubscribe
Authorization: Bearer {token}
Content-Type: application/json

{
  "endpoint": "https://fcm.googleapis.com/fcm/send/..."
}
```

### Test Notification

```http
POST /api/v1/push/test
Authorization: Bearer {token}
```

### Get Notifications

```http
GET /api/v1/notifications?limit=50&unread_only=true
Authorization: Bearer {token}
```

### Get Unread Count

```http
GET /api/v1/notifications/unread-count
Authorization: Bearer {token}
```

### Mark as Read

```http
POST /api/v1/notifications/{notificationId}/mark-as-read
Authorization: Bearer {token}
```

### Mark All as Read

```http
POST /api/v1/notifications/mark-all-as-read
Authorization: Bearer {token}
```

### Update Preferences

```http
PUT /api/v1/notifications/preferences
Authorization: Bearer {token}
Content-Type: application/json

{
  "preferences": {
    "receive-inventory-alerts": true,
    "receive-sales-alerts": true,
    "receive-invoice-alerts": false
  }
}
```

## Notification Types

### Available Notification Types

- `info`: General information (blue)
- `success`: Success messages (green)
- `warning`: Warning messages (yellow)
- `error`: Error messages (red)

### Notification Permissions

Users receive notifications based on their permissions:

- `receive-inventory-alerts`: Inventory events
- `receive-sales-alerts`: Sales events
- `receive-invoice-alerts`: Invoice events
- `receive-payment-alerts`: Payment events
- `receive-procurement-alerts`: Procurement events
- `receive-manufacturing-alerts`: Manufacturing events
- `receive-warehouse-alerts`: Warehouse events
- `receive-crm-alerts`: CRM events

## Browser Support

### Supported Browsers

- ✅ Chrome 42+
- ✅ Firefox 44+
- ✅ Edge 17+
- ✅ Opera 37+
- ✅ Safari 16+ (macOS 13+, iOS 16.4+)

### Not Supported

- ❌ Internet Explorer
- ❌ Safari < 16
- ❌ Older mobile browsers

### Feature Detection

Always check for support before enabling notifications:

```javascript
const isSupported = 
  'Notification' in window && 
  'serviceWorker' in navigator && 
  'PushManager' in window;
```

## Troubleshooting

### Notifications Not Appearing

1. **Check Permission**: Ensure notification permission is granted
2. **Check Service Worker**: Verify service worker is registered
3. **Check Subscription**: Verify push subscription exists
4. **Check Backend**: Verify VAPID keys are configured
5. **Check Queue**: Ensure queue workers are running

### Permission Denied

If users deny permission, they must manually enable it in browser settings:

- **Chrome**: Settings → Privacy and Security → Site Settings → Notifications
- **Firefox**: Preferences → Privacy & Security → Permissions → Notifications
- **Safari**: Settings → Websites → Notifications

### Service Worker Issues

```javascript
// Unregister and re-register service worker
navigator.serviceWorker.getRegistrations().then(registrations => {
  for (let registration of registrations) {
    registration.unregister();
  }
});
```

### Subscription Expired

Subscriptions can expire. The system automatically removes expired subscriptions when sending notifications.

## Security Considerations

### VAPID Keys

- **Private key**: Keep secret, never commit to version control
- **Public key**: Can be safely exposed to frontend
- **Subject**: Should be a valid mailto: or https: URL

### HTTPS Required

Push notifications **only work on HTTPS** (except localhost for development).

### User Privacy

- Never send sensitive data in push notifications
- Users can disable notifications at any time
- Respect user notification preferences

## Performance

### Optimization Tips

1. **Batch Notifications**: Use bulk sending for multiple users
2. **Queue Processing**: Always send via queues, never synchronously
3. **Expired Cleanup**: Regularly clean up expired subscriptions
4. **Retry Logic**: Implement retry for failed sends

### Monitoring

Monitor notification delivery:

```bash
# Check failed jobs
php artisan queue:failed

# Monitor queue performance
php artisan horizon (if installed)
```

## Testing

### Manual Testing

1. Enable notifications in your browser
2. Subscribe via the UI
3. Use the "Test" button to send a test notification
4. Verify notification appears

### Automated Testing

```php
use App\Services\WebPush\WebPushService;
use App\Models\User;

public function test_push_notification_sent()
{
    $user = User::factory()->create();
    $subscription = PushSubscription::factory()->create([
        'user_id' => $user->id,
    ]);
    
    $webPushService = app(WebPushService::class);
    $result = $webPushService->sendToUser($user, [
        'title' => 'Test',
        'body' => 'Test notification',
    ]);
    
    $this->assertTrue($result['success']);
}
```

## Production Checklist

- [ ] Generate and configure VAPID keys
- [ ] Run migrations
- [ ] Configure queue workers with Supervisor
- [ ] Enable HTTPS
- [ ] Register service worker
- [ ] Test on all target browsers
- [ ] Set up monitoring and alerting
- [ ] Configure retry policies
- [ ] Document for users

## Summary

AutoERP's native push notification system provides:

- ✅ **No third-party dependencies**: Direct browser Web Push API
- ✅ **Secure**: VAPID authentication
- ✅ **Privacy-focused**: User control and preferences
- ✅ **Scalable**: Queue-based async processing
- ✅ **Reliable**: Automatic retry and cleanup
- ✅ **Real-time**: Instant notification delivery
- ✅ **Cross-platform**: Works on desktop and mobile browsers

For questions or issues, consult the development team.
