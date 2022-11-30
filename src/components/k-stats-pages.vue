<template>
  <k-table
    :index="false"
    :columns="{
      name: { label: 'Page', type: 'stats-page', mobile: true },
      visits: { label: 'Visits', width: '8em', mobile: true },
    }"
    :rows="data"
  />
</template>

<script lang="ts">
import type { PropType } from 'vue'
import { Stats } from '../types'
import { slugifyPath } from '../utils'

export default {
  props: {
    stats: { type: Object as PropType<Stats>, required: true },
    urls: { type: Object as PropType<Record<string, string>>, required: true },
  },

  computed: {
    data() {
      let totalVisits = 0
      const data = {} as Record<
        string,
        { name: string; visits: number; percent: number; url: string }
      >

      for (const { paths, missing } of Object.values(this.stats)) {
        if (missing) continue
        for (const [path, { counters, title }] of Object.entries(paths)) {
          const name = title || path
          const slug = slugifyPath(path)
          const url = this.urls.page.replace('{{slug}}', slug)
          data[path] ??= { name, visits: 0, percent: 0, url }
          data[path].visits += counters.visits
          totalVisits += counters.visits
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
