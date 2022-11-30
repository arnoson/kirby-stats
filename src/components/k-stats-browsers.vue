<template>
  <k-table
    class="k-stats-browsers"
    :index="false"
    :columns="{
      name: { label: 'Browser', type: 'stats-percent', mobile: true },
      visits: { label: 'Visits', width: '8em', mobile: true },
    }"
    :rows="data"
  />
</template>

<script lang="ts">
import type { PropType } from 'vue'
import { browsers, Stats } from '../types'

export default {
  props: {
    stats: { type: Object as PropType<Stats>, required: true },
  },

  computed: {
    data() {
      let totalVisits = 0
      const data = {} as Record<
        string,
        { name: string; visits: number; percent: number }
      >

      for (const { paths, missing } of Object.values(this.stats)) {
        if (missing) continue
        for (const { counters } of Object.values(paths)) {
          for (const name of browsers) {
            data[name] ??= { name, visits: 0, percent: 0 }
            data[name].visits += counters[name]
            totalVisits += counters[name]
          }
        }
      }

      return Object.values(data)
        .map((v) => ({
          ...v,
          percent: (v.visits / totalVisits) * 100,
        }))
        .sort((a, b) => b.percent - a.percent)
    },
  },
}
</script>
