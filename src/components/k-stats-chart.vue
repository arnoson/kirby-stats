<template>
  <div class="k-stat k-stats-chart" ref="chart">
    <!-- <pre>{{ JSON.stringify(stats, null, 2) }}</pre> -->
    <dt class="k-stat-label">Visits</dt>
    <dd class="k-stat-value">200</dd>
    <dd class="k-stat-info">Info</dd>
  </div>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue'
import { Stats, Interval } from '../types'
import { LineChart } from 'chartist'
import 'chartist/dist/index.css'

export default defineComponent({
  props: {
    stats: { type: Object as PropType<Stats>, required: true },
    interval: { type: Number as PropType<Interval>, required: true },
    name: String,
  },

  chart: undefined as LineChart | undefined,

  computed: {
    chartData() {
      return Object.values(this.stats).map((v) => v.Visits)
    },
    chartLabels() {
      return Object.values(this.stats).map((v) => v.Label)
    },
  },

  mounted() {
    // @ts-ignore
    this.chart = new LineChart(
      // @ts-ignore
      this.$refs.chart,
      { series: [this.chartData], labels: this.chartLabels },
      {
        low: 0,
        fullWidth: true,
        lineSmooth: false,
        showArea: false,
        showLine: true,
        chartPadding: { top: 0, left: 0, right: 0, bottom: 0 },
        axisX: { showGrid: false },
        axisY: {
          labelInterpolationFnc: (v, i) => (i === 0 ? '' : v),
        },
      },
      [
        [
          'screen and (max-width: 1300px)',
          {
            axisX: {
              labelInterpolationFnc: (v, i) => (i % 3 === 0 ? v : null),
            },
          },
        ],
      ]
    )
  },

  watch: {
    chartData(data) {
      // @ts-ignore
      this.chart.update({
        series: [this.chartData],
        labels: this.chartLabels,
      })
    },
  },
})
</script>

<style>
.k-stat.k-stats-chart {
  height: 400px;
  padding: 3rem 3rem 0 0.7rem;
}

.k-stats-chart svg {
  overflow: visible;
}

.ct-point {
  display: none;
}

.ct-line {
  stroke-width: 4px;
  stroke-linejoin: round;
  stroke-linecap: round;
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
</style>
