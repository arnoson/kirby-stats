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
    type: { type: String, required: true },
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
        isFinished: true,
      }

      for (const { label, paths, missing, now } of Object.values(this.stats)) {
        data.labels.push(label)
        if (missing) {
          data.views.push(null)
          data.visits.push(null)
          continue
        }

        // If (the last) entry's time interval is not yet finished we'll set
        // a flag to render a dashed line, indicating that the counters are not
        // final yet.
        if (now) data.isFinished = false

        const [views, visits] = Object.values(paths).reduce(
          ([views, visits], { counters }) => [
            views + counters.views,
            visits + counters.visits,
          ],
          [0, 0],
        )

        data.views.push(views)
        data.visits.push(visits)
        data.totalViews += views
        data.totalVisits += visits
      }

      return data
    },

    series() {
      const data = this.type === 'views' ? this.data.views : this.data.visits
      if (this.data.isFinished) return [data]

      const trimmedData = this.trimData(data)
      const finishedData = trimmedData.slice(0, -1)
      const unfinishedData = trimmedData.map((value, index) =>
        index < finishedData.length - 1 ? null : value,
      )

      console.log(unfinishedData)

      return [finishedData, unfinishedData]
    },
  },

  mounted() {
    // @ts-ignore
    this.chart = new LineChart(
      // @ts-ignore
      this.$refs.chart,
      {
        series: this.series,
        labels: this.data.labels,
      },
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
      ],
    )
  },

  watch: {
    series(value) {
      // @ts-ignore
      this.chart.update({
        series: value,
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

    trimData<T>(data: Array<T | null>): Array<T | null> {
      for (let i = data.length - 1; i >= 0; i--) {
        if (data[i] !== null) return data.slice(0, i + 1)
      }
      return data
    },
  },
}
</script>

<style>
.k-stats-chart {
  &.k-stat {
    overflow: hidden;
    padding: 0rem 3rem 0.3rem 0.7rem;
  }

  & .k-button.active {
    color: blue;
  }

  & svg {
    overflow: visible;
  }

  & .ct-point {
    stroke-width: 6px;
    stroke: var(--color-blue);
  }

  & .ct-line {
    stroke-width: 4px;
    stroke-linejoin: round;
    stroke-linecap: round;
    stroke: var(--color-blue);
  }

  & .ct-series-b .ct-line {
    stroke-dasharray: 6;
  }

  & .ct-area {
    /* Fill and opacity match `--color-blue-200`. */
    fill: var(--color-blue);
    fill-opacity: 0.29;
  }

  & .ct-label.ct-vertical.ct-start,
  & .ct-label.ct-vertical.ct-end {
    transform: translateY(50%);
    align-items: center;
  }

  & .ct-label.ct-horizontal.ct-start,
  & .ct-label.ct-horizontal.ct-end {
    white-space: nowrap;
    transform: translateX(-50%);
    justify-content: center;
  }

  & .ct-label.ct-horizontal {
    margin-top: 0.2rem;
  }
}

.k-stats-chart-chartist-container {
  padding-top: 2rem;
  height: 400px;
}

.k-stats-chart-count {
  font-size: 1.25rem;
  margin-bottom: var(--spacing-1);
}

.k-stats-chart-label {
  font-size: var(--text-xs);
}
</style>
