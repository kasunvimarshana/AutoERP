import { ref, onMounted, onUnmounted } from 'vue';
import { useNotificationsStore } from '@/stores/notifications';
import { useAuthStore } from '@/stores/auth';

/**
 * Real-Time Updates Composable
 * 
 * Provides real-time functionality using Laravel Echo and WebSockets.
 * Supports:
 * - Private user channels
 * - Presence channels (who's online)
 * - Model events (created, updated, deleted)
 * - Custom events
 * - Automatic reconnection
 */
export function useRealTimeUpdates() {
  const notificationsStore = useNotificationsStore();
  const authStore = useAuthStore();
  
  const connected = ref(false);
  const channels = ref<Map<string, any>>(new Map());
  const listeners = ref<Map<string, Function[]>>(new Map());

  /**
   * Initialize Echo connection
   */
  const initialize = () => {
    if (!window.Echo) {
      console.warn('Laravel Echo not initialized');
      return false;
    }

    connected.value = true;
    console.log('Real-time updates initialized');
    return true;
  };

  /**
   * Subscribe to user's private channel
   */
  const subscribeToUserChannel = () => {
    if (!window.Echo || !authStore.user?.id) return;

    const channelName = `user.${authStore.user.id}`;
    const channel = window.Echo.private(channelName);

    // Listen for notifications
    channel.notification((notification: any) => {
      notificationsStore.addNotification({
        id: notification.id || Date.now().toString(),
        type: notification.type || 'info',
        title: notification.title || 'New Notification',
        message: notification.message,
        data: notification.data,
        read: false,
        created_at: new Date().toISOString()
      });
    });

    channels.value.set(channelName, channel);
    console.log(`Subscribed to channel: ${channelName}`);

    return channel;
  };

  /**
   * Subscribe to a model's events (created, updated, deleted)
   */
  const subscribeToModel = (
    modelType: string,
    modelId: string | number,
    callbacks: {
      onCreated?: (data: any) => void;
      onUpdated?: (data: any) => void;
      onDeleted?: (data: any) => void;
    }
  ) => {
    if (!window.Echo) return;

    const channelName = `${modelType}.${modelId}`;
    const channel = window.Echo.private(channelName);

    if (callbacks.onCreated) {
      channel.listen('.created', callbacks.onCreated);
    }

    if (callbacks.onUpdated) {
      channel.listen('.updated', callbacks.onUpdated);
    }

    if (callbacks.onDeleted) {
      channel.listen('.deleted', callbacks.onDeleted);
    }

    channels.value.set(channelName, channel);
    console.log(`Subscribed to model channel: ${channelName}`);

    return channel;
  };

  /**
   * Subscribe to a custom channel
   */
  const subscribe = (
    channelName: string,
    eventName: string,
    callback: (data: any) => void,
    channelType: 'public' | 'private' | 'presence' = 'private'
  ) => {
    if (!window.Echo) return;

    let channel = channels.value.get(channelName);

    if (!channel) {
      switch (channelType) {
        case 'public':
          channel = window.Echo.channel(channelName);
          break;
        case 'private':
          channel = window.Echo.private(channelName);
          break;
        case 'presence':
          channel = window.Echo.join(channelName);
          break;
      }

      channels.value.set(channelName, channel);
    }

    channel.listen(eventName, callback);

    // Track listeners for cleanup
    const listenerKey = `${channelName}:${eventName}`;
    const existing = listeners.value.get(listenerKey) || [];
    existing.push(callback);
    listeners.value.set(listenerKey, existing);

    console.log(`Subscribed to ${channelName} - ${eventName}`);

    return () => unsubscribe(channelName, eventName, callback);
  };

  /**
   * Unsubscribe from a channel event
   */
  const unsubscribe = (
    channelName: string,
    eventName: string,
    callback?: (data: any) => void
  ) => {
    const channel = channels.value.get(channelName);
    if (!channel) return;

    if (callback) {
      channel.stopListening(eventName, callback);
    } else {
      channel.stopListening(eventName);
    }

    const listenerKey = `${channelName}:${eventName}`;
    listeners.value.delete(listenerKey);

    console.log(`Unsubscribed from ${channelName} - ${eventName}`);
  };

  /**
   * Leave a channel
   */
  const leave = (channelName: string) => {
    const channel = channels.value.get(channelName);
    if (channel) {
      window.Echo?.leave(channelName);
      channels.value.delete(channelName);
      
      // Remove all listeners for this channel
      Array.from(listeners.value.keys())
        .filter(key => key.startsWith(`${channelName}:`))
        .forEach(key => listeners.value.delete(key));

      console.log(`Left channel: ${channelName}`);
    }
  };

  /**
   * Leave all channels and cleanup
   */
  const cleanup = () => {
    channels.value.forEach((_, channelName) => {
      leave(channelName);
    });

    channels.value.clear();
    listeners.value.clear();
    connected.value = false;

    console.log('Real-time updates cleaned up');
  };

  /**
   * Subscribe to presence channel (who's online)
   */
  const subscribeToPresence = (
    channelName: string,
    callbacks: {
      onJoin?: (users: any[]) => void;
      onLeave?: (user: any) => void;
      onHere?: (users: any[]) => void;
    }
  ) => {
    if (!window.Echo) return;

    const channel = window.Echo.join(channelName);

    if (callbacks.onHere) {
      channel.here(callbacks.onHere);
    }

    if (callbacks.onJoin) {
      channel.joining(callbacks.onJoin);
    }

    if (callbacks.onLeave) {
      channel.leaving(callbacks.onLeave);
    }

    channels.value.set(channelName, channel);
    console.log(`Subscribed to presence channel: ${channelName}`);

    return channel;
  };

  /**
   * Broadcast an event (client-side events)
   */
  const whisper = (channelName: string, eventName: string, data: any) => {
    const channel = channels.value.get(channelName);
    if (channel && typeof channel.whisper === 'function') {
      channel.whisper(eventName, data);
      console.log(`Whispered to ${channelName}: ${eventName}`);
    }
  };

  /**
   * Listen to whispered events
   */
  const listenForWhisper = (
    channelName: string,
    eventName: string,
    callback: (data: any) => void
  ) => {
    const channel = channels.value.get(channelName);
    if (channel && typeof channel.listenForWhisper === 'function') {
      channel.listenForWhisper(eventName, callback);
      console.log(`Listening for whisper on ${channelName}: ${eventName}`);
    }
  };

  /**
   * Get connection status
   */
  const getStatus = () => {
    return {
      connected: connected.value,
      activeChannels: Array.from(channels.value.keys()),
      activeListeners: Array.from(listeners.value.keys())
    };
  };

  // Lifecycle hooks
  onMounted(() => {
    if (initialize() && authStore.isAuthenticated) {
      subscribeToUserChannel();
    }
  });

  onUnmounted(() => {
    cleanup();
  });

  return {
    connected,
    initialize,
    subscribe,
    unsubscribe,
    leave,
    cleanup,
    subscribeToUserChannel,
    subscribeToModel,
    subscribeToPresence,
    whisper,
    listenForWhisper,
    getStatus
  };
}
