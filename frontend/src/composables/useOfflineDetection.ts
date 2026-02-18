/**
 * Offline Detection and Network Status Composable
 * Provides real-time network status monitoring
 */

import { ref, computed, onMounted, onUnmounted } from 'vue';

export interface NetworkStatus {
  online: boolean;
  effectiveType?: string; // '4g', '3g', '2g', 'slow-2g'
  downlink?: number; // Mbps
  rtt?: number; // Round trip time in ms
  saveData?: boolean; // User has enabled data saver
}

const isOnline = ref(navigator.onLine);
const effectiveType = ref<string>('unknown');
const downlink = ref<number>(0);
const rtt = ref<number>(0);
const saveData = ref<boolean>(false);
const lastOnlineAt = ref<Date | null>(null);
const lastOfflineAt = ref<Date | null>(null);

// Track offline duration
let offlineStartTime: number | null = null;

export function useOfflineDetection() {
  /**
   * Update network information
   */
  const updateNetworkInfo = () => {
    const connection = (navigator as any).connection || 
                      (navigator as any).mozConnection || 
                      (navigator as any).webkitConnection;
    
    if (connection) {
      effectiveType.value = connection.effectiveType || 'unknown';
      downlink.value = connection.downlink || 0;
      rtt.value = connection.rtt || 0;
      saveData.value = connection.saveData || false;
    }
  };

  /**
   * Handle online event
   */
  const handleOnline = () => {
    isOnline.value = true;
    lastOnlineAt.value = new Date();
    
    // Calculate offline duration if we were offline
    if (offlineStartTime) {
      const offlineDuration = Date.now() - offlineStartTime;
      offlineStartTime = null;
      
      // Trigger custom event with offline duration
      window.dispatchEvent(new CustomEvent('network-restored', {
        detail: { offlineDuration }
      }));
    }

    updateNetworkInfo();
  };

  /**
   * Handle offline event
   */
  const handleOffline = () => {
    isOnline.value = false;
    lastOfflineAt.value = new Date();
    offlineStartTime = Date.now();

    // Trigger custom event
    window.dispatchEvent(new CustomEvent('network-lost'));
  };

  /**
   * Handle connection change
   */
  const handleConnectionChange = () => {
    updateNetworkInfo();
  };

  /**
   * Setup event listeners
   */
  const setupListeners = () => {
    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    const connection = (navigator as any).connection || 
                      (navigator as any).mozConnection || 
                      (navigator as any).webkitConnection;
    
    if (connection) {
      connection.addEventListener('change', handleConnectionChange);
    }

    // Initial update
    updateNetworkInfo();
  };

  /**
   * Remove event listeners
   */
  const removeListeners = () => {
    window.removeEventListener('online', handleOnline);
    window.removeEventListener('offline', handleOffline);

    const connection = (navigator as any).connection || 
                      (navigator as any).mozConnection || 
                      (navigator as any).webkitConnection;
    
    if (connection) {
      connection.removeEventListener('change', handleConnectionChange);
    }
  };

  // Computed
  const networkStatus = computed<NetworkStatus>(() => ({
    online: isOnline.value,
    effectiveType: effectiveType.value,
    downlink: downlink.value,
    rtt: rtt.value,
    saveData: saveData.value,
  }));

  const isSlowConnection = computed(() => {
    return effectiveType.value === '2g' || effectiveType.value === 'slow-2g' || downlink.value < 0.5;
  });

  const isFastConnection = computed(() => {
    return effectiveType.value === '4g' && downlink.value > 2;
  });

  const connectionQuality = computed(() => {
    if (!isOnline.value) return 'offline';
    if (isSlowConnection.value) return 'slow';
    if (isFastConnection.value) return 'fast';
    return 'moderate';
  });

  /**
   * Check if we can perform an action that requires network
   */
  const canPerformNetworkAction = computed(() => {
    return isOnline.value && !isSlowConnection.value;
  });

  /**
   * Get offline duration in milliseconds
   */
  const getOfflineDuration = () => {
    if (!lastOfflineAt.value) return 0;
    if (isOnline.value && lastOnlineAt.value) {
      return lastOnlineAt.value.getTime() - lastOfflineAt.value.getTime();
    }
    return Date.now() - lastOfflineAt.value.getTime();
  };

  /**
   * Ping server to check actual connectivity
   */
  const pingServer = async (url: string = '/api/v1/health', timeout: number = 5000): Promise<boolean> => {
    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), timeout);

      const response = await fetch(url, {
        method: 'HEAD',
        signal: controller.signal,
        cache: 'no-cache',
      });

      clearTimeout(timeoutId);
      return response.ok;
    } catch (error) {
      return false;
    }
  };

  /**
   * Subscribe to network status changes
   */
  const onNetworkChange = (callback: (status: NetworkStatus) => void) => {
    const handler = () => callback(networkStatus.value);
    window.addEventListener('online', handler);
    window.addEventListener('offline', handler);

    const connection = (navigator as any).connection || 
                      (navigator as any).mozConnection || 
                      (navigator as any).webkitConnection;
    
    if (connection) {
      connection.addEventListener('change', handler);
    }

    return () => {
      window.removeEventListener('online', handler);
      window.removeEventListener('offline', handler);
      if (connection) {
        connection.removeEventListener('change', handler);
      }
    };
  };

  /**
   * Subscribe to network restored event
   */
  const onNetworkRestored = (callback: (offlineDuration: number) => void) => {
    const handler = (event: Event) => {
      const customEvent = event as CustomEvent;
      callback(customEvent.detail.offlineDuration);
    };
    window.addEventListener('network-restored', handler);

    return () => {
      window.removeEventListener('network-restored', handler);
    };
  };

  /**
   * Subscribe to network lost event
   */
  const onNetworkLost = (callback: () => void) => {
    window.addEventListener('network-lost', callback);

    return () => {
      window.removeEventListener('network-lost', callback);
    };
  };

  // Lifecycle
  onMounted(() => {
    setupListeners();
  });

  onUnmounted(() => {
    removeListeners();
  });

  return {
    // State
    isOnline,
    networkStatus,
    effectiveType,
    downlink,
    rtt,
    saveData,
    lastOnlineAt,
    lastOfflineAt,

    // Computed
    isSlowConnection,
    isFastConnection,
    connectionQuality,
    canPerformNetworkAction,

    // Methods
    updateNetworkInfo,
    getOfflineDuration,
    pingServer,
    onNetworkChange,
    onNetworkRestored,
    onNetworkLost,
  };
}

/**
 * Global offline detection setup
 * Call this in main.ts to setup global offline detection
 */
export function setupOfflineDetection() {
  const { onNetworkLost, onNetworkRestored } = useOfflineDetection();

  // Show notification when network is lost
  onNetworkLost(() => {
    console.warn('[Network] Connection lost');
    
    // Could dispatch a notification event
    window.dispatchEvent(new CustomEvent('show-notification', {
      detail: {
        type: 'warning',
        message: 'You are currently offline. Some features may be unavailable.',
        duration: 0, // Persistent until dismissed
      }
    }));
  });

  // Show notification when network is restored
  onNetworkRestored((offlineDuration) => {
    console.log(`[Network] Connection restored after ${offlineDuration}ms`);
    
    // Could dispatch a notification event
    window.dispatchEvent(new CustomEvent('show-notification', {
      detail: {
        type: 'success',
        message: 'Connection restored. Syncing data...',
        duration: 3000,
      }
    }));

    // Could trigger data sync here
    window.dispatchEvent(new CustomEvent('sync-offline-data'));
  });
}
