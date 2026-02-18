/**
 * Performance Monitoring Composable
 * Provides utilities for tracking and measuring performance
 */

import { ref, onMounted, onUnmounted } from 'vue';

export interface PerformanceMetric {
  name: string;
  duration: number;
  timestamp: Date;
  type: 'navigation' | 'resource' | 'measure' | 'custom';
  metadata?: Record<string, any>;
}

const metrics = ref<PerformanceMetric[]>([]);
const observers: PerformanceObserver[] = [];

export function usePerformance() {
  /**
   * Measure function execution time
   */
  const measure = async <T>(
    name: string,
    fn: () => Promise<T> | T,
    metadata?: Record<string, any>
  ): Promise<{ result: T; duration: number }> => {
    const startTime = performance.now();
    
    try {
      const result = await fn();
      const duration = performance.now() - startTime;
      
      recordMetric({
        name,
        duration,
        timestamp: new Date(),
        type: 'custom',
        metadata,
      });

      return { result, duration };
    } catch (error) {
      const duration = performance.now() - startTime;
      recordMetric({
        name: `${name} (failed)`,
        duration,
        timestamp: new Date(),
        type: 'custom',
        metadata: { ...metadata, error: String(error) },
      });
      throw error;
    }
  };

  /**
   * Mark a performance point
   */
  const mark = (name: string) => {
    if (performance.mark) {
      performance.mark(name);
    }
  };

  /**
   * Measure between two marks
   */
  const measureBetween = (name: string, startMark: string, endMark: string) => {
    if (performance.measure) {
      try {
        performance.measure(name, startMark, endMark);
        
        const measure = performance.getEntriesByName(name, 'measure')[0];
        if (measure) {
          recordMetric({
            name,
            duration: measure.duration,
            timestamp: new Date(),
            type: 'measure',
          });
        }
      } catch (error) {
        console.warn('Performance measure failed:', error);
      }
    }
  };

  /**
   * Record a performance metric
   */
  const recordMetric = (metric: PerformanceMetric) => {
    metrics.value.push(metric);

    // Log slow operations in development
    if (import.meta.env.DEV && metric.duration > 1000) {
      console.warn(`[Performance] Slow operation: ${metric.name} took ${metric.duration.toFixed(2)}ms`);
    }

    // Limit metrics array size
    if (metrics.value.length > 100) {
      metrics.value = metrics.value.slice(-50);
    }
  };

  /**
   * Get navigation timing metrics
   */
  const getNavigationTiming = () => {
    const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming;
    if (!navigation) return null;

    return {
      dns: navigation.domainLookupEnd - navigation.domainLookupStart,
      tcp: navigation.connectEnd - navigation.connectStart,
      request: navigation.responseStart - navigation.requestStart,
      response: navigation.responseEnd - navigation.responseStart,
      domProcessing: navigation.domComplete - navigation.domInteractive,
      domContentLoaded: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
      loadComplete: navigation.loadEventEnd - navigation.loadEventStart,
      totalTime: navigation.loadEventEnd - navigation.fetchStart,
    };
  };

  /**
   * Get first paint metrics
   */
  const getPaintMetrics = () => {
    const paints = performance.getEntriesByType('paint');
    const result: Record<string, number> = {};

    paints.forEach((paint) => {
      result[paint.name] = paint.startTime;
    });

    return result;
  };

  /**
   * Get largest contentful paint
   */
  const getLargestContentfulPaint = (): Promise<number> => {
    return new Promise((resolve) => {
      if (!('PerformanceObserver' in window)) {
        resolve(0);
        return;
      }

      try {
        const observer = new PerformanceObserver((list) => {
          const entries = list.getEntries();
          const lastEntry = entries[entries.length - 1] as any;
          resolve(lastEntry.startTime);
        });

        observer.observe({ entryTypes: ['largest-contentful-paint'] });

        // Timeout after 10 seconds
        setTimeout(() => {
          observer.disconnect();
          resolve(0);
        }, 10000);
      } catch (error) {
        resolve(0);
      }
    });
  };

  /**
   * Get first input delay
   */
  const getFirstInputDelay = (): Promise<number> => {
    return new Promise((resolve) => {
      if (!('PerformanceObserver' in window)) {
        resolve(0);
        return;
      }

      try {
        const observer = new PerformanceObserver((list) => {
          const entries = list.getEntries();
          const firstInput = entries[0] as any;
          resolve(firstInput.processingStart - firstInput.startTime);
        });

        observer.observe({ entryTypes: ['first-input'] });

        // Timeout after 30 seconds
        setTimeout(() => {
          observer.disconnect();
          resolve(0);
        }, 30000);
      } catch (error) {
        resolve(0);
      }
    });
  };

  /**
   * Get cumulative layout shift
   */
  const getCumulativeLayoutShift = (): Promise<number> => {
    return new Promise((resolve) => {
      if (!('PerformanceObserver' in window)) {
        resolve(0);
        return;
      }

      let clsValue = 0;

      try {
        const observer = new PerformanceObserver((list) => {
          for (const entry of list.getEntries() as any[]) {
            if (!entry.hadRecentInput) {
              clsValue += entry.value;
            }
          }
        });

        observer.observe({ entryTypes: ['layout-shift'] });

        // Calculate final value after 10 seconds
        setTimeout(() => {
          observer.disconnect();
          resolve(clsValue);
        }, 10000);
      } catch (error) {
        resolve(0);
      }
    });
  };

  /**
   * Get core web vitals
   */
  const getCoreWebVitals = async () => {
    const [lcp, fid, cls] = await Promise.all([
      getLargestContentfulPaint(),
      getFirstInputDelay(),
      getCumulativeLayoutShift(),
    ]);

    return {
      lcp, // Good: < 2.5s
      fid, // Good: < 100ms
      cls, // Good: < 0.1
    };
  };

  /**
   * Get resource loading times
   */
  const getResourceTimings = (filter?: string) => {
    const resources = performance.getEntriesByType('resource') as PerformanceResourceTiming[];
    
    return resources
      .filter(resource => !filter || resource.name.includes(filter))
      .map(resource => ({
        name: resource.name,
        duration: resource.duration,
        size: (resource as any).transferSize || 0,
        type: resource.initiatorType,
      }))
      .sort((a, b) => b.duration - a.duration);
  };

  /**
   * Get memory usage (if available)
   */
  const getMemoryUsage = () => {
    const memory = (performance as any).memory;
    if (!memory) return null;

    return {
      used: memory.usedJSHeapSize,
      total: memory.totalJSHeapSize,
      limit: memory.jsHeapSizeLimit,
      percentage: (memory.usedJSHeapSize / memory.jsHeapSizeLimit) * 100,
    };
  };

  /**
   * Clear all performance entries
   */
  const clearMetrics = () => {
    metrics.value = [];
    if (performance.clearMarks) {
      performance.clearMarks();
    }
    if (performance.clearMeasures) {
      performance.clearMeasures();
    }
  };

  /**
   * Get performance report
   */
  const getReport = () => {
    return {
      navigationTiming: getNavigationTiming(),
      paintMetrics: getPaintMetrics(),
      resourceTimings: getResourceTimings(),
      memoryUsage: getMemoryUsage(),
      customMetrics: metrics.value,
    };
  };

  /**
   * Log performance report to console
   */
  const logReport = () => {
    const report = getReport();
    console.group('📊 Performance Report');
    console.table(report.navigationTiming);
    console.table(report.paintMetrics);
    console.log('Memory Usage:', report.memoryUsage);
    console.log('Custom Metrics:', report.customMetrics);
    console.groupEnd();
  };

  /**
   * Monitor long tasks
   */
  const monitorLongTasks = () => {
    if (!('PerformanceObserver' in window)) return;

    try {
      const observer = new PerformanceObserver((list) => {
        for (const entry of list.getEntries()) {
          console.warn(`[Performance] Long task detected: ${entry.duration.toFixed(2)}ms`);
          recordMetric({
            name: 'long-task',
            duration: entry.duration,
            timestamp: new Date(),
            type: 'custom',
          });
        }
      });

      observer.observe({ entryTypes: ['longtask'] });
      observers.push(observer);
    } catch (error) {
      console.warn('Long task monitoring not supported');
    }
  };

  /**
   * Cleanup observers
   */
  const cleanup = () => {
    observers.forEach(observer => observer.disconnect());
    observers.length = 0;
  };

  // Lifecycle
  onMounted(() => {
    if (import.meta.env.DEV) {
      monitorLongTasks();
    }
  });

  onUnmounted(() => {
    cleanup();
  });

  return {
    metrics,
    measure,
    mark,
    measureBetween,
    recordMetric,
    getNavigationTiming,
    getPaintMetrics,
    getCoreWebVitals,
    getResourceTimings,
    getMemoryUsage,
    clearMetrics,
    getReport,
    logReport,
    monitorLongTasks,
  };
}

/**
 * Setup global performance monitoring
 */
export function setupPerformanceMonitoring() {
  const { monitorLongTasks, getCoreWebVitals } = usePerformance();

  // Monitor long tasks in development
  if (import.meta.env.DEV) {
    monitorLongTasks();
  }

  // Report core web vitals when page loads
  window.addEventListener('load', async () => {
    // Wait a bit for metrics to be collected
    setTimeout(async () => {
      const vitals = await getCoreWebVitals();
      console.log('[Performance] Core Web Vitals:', vitals);
      
      // Warn about poor metrics
      if (vitals.lcp > 2500) {
        console.warn('[Performance] LCP is above 2.5s threshold:', vitals.lcp);
      }
      if (vitals.fid > 100) {
        console.warn('[Performance] FID is above 100ms threshold:', vitals.fid);
      }
      if (vitals.cls > 0.1) {
        console.warn('[Performance] CLS is above 0.1 threshold:', vitals.cls);
      }
    }, 1000);
  });

  console.log('[Performance] Performance monitoring initialized');
}
