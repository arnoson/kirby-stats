<template>
  <div class="k-stat k-stats-chart">
    <div class="k-stats-chart-chartist-container" ref="chart"></div>
    <!-- <pre>{{ JSON.stringify(stats, null, 2) }}</pre> -->
  </div>
</template>

<script lang="ts">
import { Label, LineChart } from 'chartist'
import 'chartist/dist/index.css'
import type { PropType } from 'vue'
import { Interval, Stats } from '../types'

export default {
  props: {
    stats: { type: Object as PropType<Stats>, required: true },
    interval: { type: String as PropType<Interval>, required: true },
    name: String,
  },

  chart: undefined as LineChart | undefined,

  computed: {
    data() {
      const data = {
        views: [] as (number | null)[],
        visits: [] as (number | null)[],
        labels: [] as string[],
        totalViews: 0,
        totalVisits: 0,
      }

      for (const { label, paths, missing } of Object.values(this.stats)) {
        data.labels.push(label)
        if (missing) {
          data.views.push(null)
          data.visits.push(null)
          continue
        }

        const [views, visits] = Object.values(paths).reduce(
          ([views, visits], path) => [views + path.views, visits + path.visits],
          [0, 0]
        )
        data.views.push(views)
        data.visits.push(visits)
        data.totalViews += views
        data.totalVisits += visits
      }

      return data
    },
  },

  mounted() {
    // @ts-ignore
    this.chart = new LineChart(
      // @ts-ignore
      this.$refs.chart,
      { series: [this.data.visits], labels: this.data.labels },
      {
        low: 0,
        fullWidth: true,
        lineSmooth: false,
        showArea: true,
        showLine: true,
        chartPadding: { top: 0, left: 0, right: 0, bottom: 0 },
        axisX: {
          showGrid: false,
          labelInterpolationFnc: this.showMaxLabels(8),
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
              labelInterpolationFnc: this.showMaxLabels(4),
            },
          },
        ],
      ]
    )
  },

  watch: {
    data() {
      // @ts-ignore
      this.chart.update({
        series: [this.data.views],
        labels: this.data.labels,
      })
    },
  },

  methods: {
    showMaxLabels(max: number) {
      return (label: Label, index: number) => {
        const { length } = this.data.labels
        if (length <= max) return label
        // Only show every n-th label/
        const n = Math.round(length / max)
        return index % n === 0 ? label : null
      }
    },
  },
}
</script>

<style lang="scss">
.k-stats-chart {
  &.k-stat {
    padding: 0rem 3rem 0.3rem 0.7rem;
  }

  &-chartist-container {
    padding-top: 2rem;
    height: 400px;
  }

  &-count {
    font-size: 1.25rem;
    margin-bottom: var(--spacing-1);
  }

  &-label {
    font-size: var(--text-xs);
  }

  k-button.active {
    color: blue;
  }

  svg {
    overflow: visible;
  }

  .ct-point {
    stroke-width: 6px;
    stroke: var(--color-blue);
  }

  .ct-line {
    stroke-width: 4px;
    stroke-linejoin: round;
    stroke-linecap: round;
    stroke: var(--color-blue);
  }

  .ct-area {
    // Fill and opacity match `--color-blue-200`.
    fill: var(--color-blue);
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
</style>
