# WebSocket Real-Time Notifications Setup Guide

## Overview

This guide explains how to set up real-time WebSocket notifications for the AutoERP system using Laravel Broadcasting with either Laravel Reverb (recommended for development/production) or Pusher (managed service).

## Architecture

```
Frontend (Vue.js + Laravel Echo)
    ↓ WebSocket Connection
Laravel Broadcasting (Redis Pub/Sub)
    ↓
Laravel Reverb / Pusher / Soketi
    ↓
Backend Event Broadcasting
```

## Setup Options

### Option 1: Laravel Reverb (Recommended - Free, Built-in)

Laravel Reverb is a first-party WebSocket server for Laravel applications.

#### Backend Setup

1. **Install Laravel Reverb**:
```bash
cd backend
composer require laravel/reverb
php artisan reverb:install
```

2. **Configure `.env`**:
```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=local-app-id
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

3. **Start Reverb Server**:
```bash
php artisan reverb:start
```

4. **Run Queue Worker** (for processing events):
```bash
php artisan queue:work --queue=high,default,low
```

#### Frontend Setup

1. **Update `.env`**:
```env
VITE_PUSHER_APP_KEY=local-key
VITE_PUSHER_HOST=localhost
VITE_PUSHER_PORT=8080
VITE_PUSHER_SCHEME=http
VITE_PUSHER_APP_CLUSTER=mt1
```

2. **Restart Development Server**:
```bash
npm run dev
```

### Option 2: Pusher (Managed Service - Easy, Scalable)

Pusher is a managed WebSocket service with a free tier.

#### Backend Setup

1. **Sign up for Pusher**: https://pusher.com/
2. **Create a Channels app** and get credentials
3. **Configure `.env`**:
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=us2  # or your region
```

4. **Run Queue Worker**:
```bash
php artisan queue:work --queue=high,default,low
```

#### Frontend Setup

1. **Update `.env`**:
```env
VITE_PUSHER_APP_KEY=your-app-key
VITE_PUSHER_HOST=
VITE_PUSHER_PORT=443
VITE_PUSHER_SCHEME=https
VITE_PUSHER_APP_CLUSTER=us2  # or your region
```

2. **Restart Development Server**:
```bash
npm run dev
```

### Option 3: Soketi (Self-Hosted, Free)

Soketi is an open-source, Pusher-compatible WebSocket server.

#### Backend Setup

1. **Install Soketi**:
```bash
npm install -g @soketi/soketi
```

2. **Create `soketi.json`**:
```json
{
  "debug": true,
  "port": 6001,
  "appManager.array.apps": [
    {
      "id": "local-app-id",
      "key": "local-key",
      "secret": "local-secret",
      "maxConnections": -1,
      "enableClientMessages": true,
      "enabled": true
    }
  ]
}
```

3. **Start Soketi**:
```bash
soketi start --config=soketi.json
```

4. **Configure `.env`**:
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=local-app-id
PUSHER_APP_KEY=local-key
PUSHER_APP_SECRET=local-secret
PUSHER_HOST=localhost
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1
```

5. **Run Queue Worker**:
```bash
php artisan queue:work --queue=high,default,low
```

#### Frontend Setup

1. **Update `.env`**:
```env
VITE_PUSHER_APP_KEY=local-key
VITE_PUSHER_HOST=localhost
VITE_PUSHER_PORT=6001
VITE_PUSHER_SCHEME=http
VITE_PUSHER_APP_CLUSTER=mt1
```

2. **Restart Development Server**:
```bash
npm run dev
```

## Broadcast Channels

The system uses the following private channels (require authentication):

### User Channels
- `App.Models.User.{userId}` - User-specific notifications
  - Events: `Illuminate\Notifications\Events\BroadcastNotificationCreated`

### Tenant Channels
- `tenant.{tenantId}.notifications` - Tenant-wide notifications
  - Events: `.notification.created`

- `tenant.{tenantId}.inventory` - Inventory updates
  - Events: `.inventory.update`, `.product.created`, `.product.updated`

- `tenant.{tenantId}.stock-alerts` - Stock level alerts
  - Events: `.stock.low`, `.stock.adjusted`

## Testing WebSocket Connection

### 1. Check WebSocket Server

#### For Reverb:
```bash
# Check if Reverb is running
php artisan reverb:check

# View Reverb logs
tail -f storage/logs/reverb.log
```

#### For Pusher:
Visit Pusher Dashboard → Debug Console

#### For Soketi:
```bash
# Check if Soketi is running
curl http://localhost:6001/
```

### 2. Test from Browser Console

After logging in, open browser console and run:

```javascript
// Check if Echo is initialized
console.log(window.Echo)

// Test connection to a channel
Echo.private('App.Models.User.1')
  .notification((notification) => {
    console.log('Received notification:', notification)
  })
```

### 3. Trigger Test Event

From Laravel tinker:

```bash
php artisan tinker
```

```php
// Send a test notification
$user = \App\Models\User::find(1);
$user->notify(new \Modules\Core\Notifications\TestNotification());
```

## Troubleshooting

### Issue: WebSocket connection fails

**Solution**:
1. Verify WebSocket server is running
2. Check firewall rules allow connections on the port
3. Verify `.env` configuration matches on backend and frontend
4. Check browser console for connection errors
5. Ensure authentication token is valid

### Issue: Events not received

**Solution**:
1. Verify queue worker is running: `php artisan queue:work`
2. Check Redis is running: `redis-cli ping`
3. Verify event is being broadcast: Check logs in `storage/logs/laravel.log`
4. Ensure channel authorization is correct in `routes/channels.php`
5. Verify user has correct permissions for private channels

### Issue: "401 Unauthorized" on channel subscription

**Solution**:
1. Verify authentication token is being sent in headers
2. Check channel authorization in `routes/channels.php`
3. Ensure user exists and has correct tenant context
4. Verify Sanctum token is valid and not expired

### Issue: Events work but no browser notifications

**Solution**:
1. Request notification permission: Click bell icon in app
2. Check browser notification settings
3. Verify HTTPS in production (required for notifications)

## Production Deployment

### Using Laravel Reverb

1. **Configure Reverb for Production**:
```env
REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SCHEME=https
```

2. **Set up SSL/TLS**: Use nginx/Apache reverse proxy with SSL

3. **Use Supervisor** to keep Reverb running:
```ini
[program:reverb]
command=php /var/www/html/artisan reverb:start
directory=/var/www/html
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
```

### Using Pusher

1. Upgrade to a paid Pusher plan for production
2. Configure environment variables with production credentials
3. Enable TLS (automatically handled by Pusher)

### General Production Checklist

- [ ] WebSocket server running with SSL/TLS
- [ ] Queue worker running as daemon (Supervisor)
- [ ] Redis configured for persistence
- [ ] Firewall allows WebSocket port
- [ ] Load balancer configured for WebSocket connections (sticky sessions)
- [ ] Monitoring set up for WebSocket health
- [ ] Rate limiting configured
- [ ] Channel authorization tested

## Performance Considerations

### Scaling

- **Horizontal Scaling**: Use Redis Cluster for pub/sub across multiple servers
- **Load Balancing**: Enable sticky sessions for WebSocket connections
- **Queue Workers**: Run multiple queue workers for event processing
- **Connection Pooling**: Monitor and limit concurrent connections

### Monitoring

Monitor these metrics:
- Active WebSocket connections
- Messages per second
- Queue depth and processing time
- Redis memory usage
- Failed job rate

## Security Best Practices

1. **Always use TLS/SSL in production**
2. **Implement rate limiting** on broadcast endpoints
3. **Validate channel authorization** strictly
4. **Sanitize notification data** before broadcasting
5. **Monitor for unusual connection patterns**
6. **Keep authentication tokens secure**
7. **Implement connection limits** per user/tenant
8. **Log broadcast events** for audit trail

## Additional Resources

- [Laravel Broadcasting Documentation](https://laravel.com/docs/broadcasting)
- [Laravel Reverb Documentation](https://laravel.com/docs/reverb)
- [Laravel Echo Documentation](https://laravel.com/docs/echo)
- [Pusher Documentation](https://pusher.com/docs)
- [Soketi Documentation](https://docs.soketi.app/)

## Support

For issues or questions:
- Check Laravel logs: `storage/logs/laravel.log`
- Check WebSocket server logs
- Enable debug mode temporarily: `APP_DEBUG=true`
- Review channel authorization in `routes/channels.php`
- Test with simple events first before complex notifications
