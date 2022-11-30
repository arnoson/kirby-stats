<template>
  <k-table
    :index="false"
    :columns="{
      path: { label: 'Page', type: 'stats-percent', mobile: true },
      visits: { label: 'Visits', width: '8em', mobile: true },
    }"
    :rows="data"
  />
</template>

<script lang="ts">
import type { PropType } from 'vue'
import { Stats } from '../types'

export default {
  props: {
    stats: { type: Object as PropType<Stats>, required: true },
  },

  computed: {
    data() {
      let totalVisits = 0
      const data = {} as Record<
        string,
        { path: string; visits: number; percent: number }
      >

      for (const { paths, missing } of Object.values(this.stats)) {
        if (missing) continue
        for (const [path, { visits }] of Object.entries(paths)) {
          data[path] ??= { path, visits: 0, percent: 0 }
          data[path].visits += visits
          totalVisits += visits
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
