<script setup lang="ts">
import { Label, LineChart } from 'chartist'
import 'chartist/dist/index.css'
import { computed, onMounted, ref, watch } from 'vue'
import { Page, Stats, Type } from '../types'

const props = defineProps<{ stats: Stats; type: Type; page?: Page }>()
const emit = defineEmits<{ (event: 'update:type', value: Type): void }>()

let chart: LineChart | undefined
const chartistContainer = ref(null)

const data = computed(() => {
  const data: (number | null)[] = []
  const labels: string[] = []
  let isFinished = true

  for (const { label, paths, missing, now } of Object.values(props.stats)) {
    labels.push(label)
    if (missing) {
      data.push(null)
      continue
    }
    // If (the last) entry's time interval is not yet finished we'll set
    // a flag to render a dashed line, indicating that the counters are not
    // final yet.
    if (now) isFinished = false

    const { type, page } = props
    let value: number | null = null

    if (!page) {
      // Handle site-wide statistics
      if (type === 'visitors') {
        value = paths['site://']?.counters.visits ?? 0
      } else {
        // Sum up all page visits/views
        value = Object.entries(paths)
          .filter(([path]) => !path.startsWith('site://'))
          .reduce((sum, [, data]) => sum + data.counters[type], 0)
      }
    } else if (props.type !== 'visitors') {
      // Handle individual page statistics (visitors is only supported for site)
      const path = page.uuid ?? page.id
      value = paths[path]?.counters[props.type] ?? 0
    }

    data.push(value)
  }

  if (isFinished) return { labels, series: [data] }

  const trimmedData = trimData(data)
  const finishedData = trimmedData.slice(0, -1)
  const unfinishedData = trimmedData.map((value, index) =>
    index < finishedData.length - 1 ? null : value,
  )

  return { labels, series: [finishedData, unfinishedData] }
})

const trimData = <T,>(data: Array<T | null>): Array<T | null> => {
  for (let i = data.length - 1; i >= 0; i--) {
    if (data[i] !== null) return data.slice(0, i + 1)
  }
  return data
}

const showMaxLabels = (max: number) => (label: Label, index: number) => {
  const { length } = data.value.labels
  if (length <= max) return label
  // Only show every n-th label/
  const n = Math.round(length / max)
  return index % n === 0 ? label : null
}

onMounted(() => {
  chart = new LineChart(
    chartistContainer.value,
    data.value,
    {
      low: 0,
      fullWidth: true,
      lineSmooth: false,
      showArea: true,
      showLine: true,
      chartPadding: { top: 0, left: 0, right: 0, bottom: 0 },
      axisX: {
        showGrid: false,
        labelInterpolationFnc: showMaxLabels(8),
      },
      axisY: {
        labelInterpolationFnc: (v, i) => (i === 0 ? '' : v),
        onlyInteger: true,
      },
    },
    [
      [
        'screen and (max-width: 800px)',
        {
          axisX: {
            labelInterpolationFnc: showMaxLabels(4),
          },
        },
      ],
    ],
  )
})

watch(data, (value) => chart?.update(value))
</script>

<template>
  <section class="k-section">
    <div class="kirby-stats-chart k-stat">
      <div class="kirby-stats-chartist-container" ref="chartistContainer"></div>
    </div>
  </section>
</template>

<style>
.kirby-stats-chart {
  /* Color tints in increments of 50 are only available since Kirby 5 */
  --kirby-stats-color-chart: light-dark(
    var(--color-blue),
    var(--color-blue-650, var(--color-blue-700))
  );

  overflow: hidden;
  padding: 0rem 3rem 0.3rem 0.7rem;

  .k-button.active {
    color: blue;
  }

  svg {
    overflow: visible;
  }

  .ct-point {
    stroke-width: 6px;
    stroke: var(--kirby-stats-color-chart);
  }

  .ct-line {
    stroke-width: 4px;
    stroke-linejoin: round;
    stroke-linecap: round;
    stroke: var(--kirby-stats-color-chart);
  }

  .ct-label {
    color: var(--color-text-dimmed);
  }

  .ct-grid {
    stroke: var(--color-border);
  }

  .ct-series-b .ct-line {
    stroke-dasharray: 6;
  }

  .ct-area {
    fill: var(--kirby-stats-color-chart);
    fill-opacity: 0.29;
  }

  .ct-label.ct-vertical.ct-start,
  .ct-label.ct-vertical.ct-end {
    transform: translateY(50%);
    align-items: center;
  }

  .ct-label.ct-horizontal.ct-start,
  .ct-label.ct-horizontal.ct-end {
    white-space: nowrap;
    transform: translateX(-50%);
    justify-content: center;
  }

  .ct-label.ct-horizontal {
    margin-top: 0.2rem;
  }
}

.kirby-stats-chartist-container {
  padding-top: 2rem;
  height: 400px;
}

.kirby-stats-type-button:not(.active):not(:hover) {
  color: var(--color-text-dimmed);
}
</style>
