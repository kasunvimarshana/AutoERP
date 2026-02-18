<template>
  <div class="chart-widget">
    <div
      ref="chartContainer"
      class="chart-container"
    >
      <canvas ref="chartCanvas" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import {
  Chart,
  ArcElement,
  LineElement,
  BarElement,
  PointElement,
  BarController,
  LineController,
  DoughnutController,
  PieController,
  CategoryScale,
  LinearScale,
  LogarithmicScale,
  RadialLinearScale,
  TimeScale,
  TimeSeriesScale,
  Decimation,
  Filler,
  Legend,
  Title,
  Tooltip,
  SubTitle
} from 'chart.js';

// Register Chart.js components
Chart.register(
  ArcElement,
  LineElement,
  BarElement,
  PointElement,
  BarController,
  LineController,
  DoughnutController,
  PieController,
  CategoryScale,
  LinearScale,
  LogarithmicScale,
  RadialLinearScale,
  TimeScale,
  TimeSeriesScale,
  Decimation,
  Filler,
  Legend,
  Title,
  Tooltip,
  SubTitle
);

interface ChartData {
  type: 'line' | 'bar' | 'pie' | 'doughnut';
  labels: string[];
  datasets: Array<{
    label: string;
    data: number[];
    backgroundColor?: string | string[];
    borderColor?: string | string[];
    borderWidth?: number;
    fill?: boolean;
  }>;
  options?: any;
}

interface Props {
  data: ChartData;
  config?: any;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  action: [{ action: string; data: any }];
}>();

const chartCanvas = ref<HTMLCanvasElement | null>(null);
const chartContainer = ref<HTMLDivElement | null>(null);
let chartInstance: Chart | null = null;

const createChart = () => {
  if (!chartCanvas.value || !props.data) return;

  // Destroy existing chart
  if (chartInstance) {
    chartInstance.destroy();
  }

  const ctx = chartCanvas.value.getContext('2d');
  if (!ctx) return;

  const defaultOptions = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
      legend: {
        display: true,
        position: 'bottom' as const,
      },
      tooltip: {
        enabled: true,
        mode: 'index' as const,
        intersect: false,
      },
    },
    onClick: (event: any, activeElements: any[]) => {
      if (activeElements.length > 0) {
        const element = activeElements[0];
        const datasetIndex = element.datasetIndex;
        const index = element.index;
        
        emit('action', {
          action: 'chart-click',
          data: {
            datasetIndex,
            index,
            label: props.data.labels[index],
            value: props.data.datasets[datasetIndex].data[index],
          },
        });
      }
    },
  };

  chartInstance = new Chart(ctx, {
    type: props.data.type,
    data: {
      labels: props.data.labels,
      datasets: props.data.datasets,
    },
    options: {
      ...defaultOptions,
      ...props.data.options,
    },
  });
};

watch(() => props.data, () => {
  createChart();
}, { deep: true });

onMounted(() => {
  createChart();
});

onBeforeUnmount(() => {
  if (chartInstance) {
    chartInstance.destroy();
  }
});
</script>

<style scoped>
.chart-container {
  @apply relative w-full;
  min-height: 300px;
}
</style>
