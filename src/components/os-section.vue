<script setup lang="ts">
import { computed } from 'vue'
import { os, Stats } from '../types'

const props = defineProps<{ stats: Stats }>()

type Row = { name: string; visits: number; percent: number }
const rows = computed(() => {
  let totalVisits = 0
  const data: Record<string, Row> = {}

  for (const { paths, missing } of Object.values(props.stats)) {
    if (missing) continue
    for (const { counters } of Object.values(paths)) {
      for (const name of os) {
        data[name] ??= { name, visits: 0, percent: 0 }
        data[name].visits += counters[name]
        totalVisits += counters[name]
      }
    }
  }

  return Object.values(data)
    .map((v) => ({
      ...v,
      percent: totalVisits ? (v.visits / totalVisits) * 100 : 0,
    }))
    .sort((a, b) => b.percent - a.percent)
})
</script>

<template>
  <k-table
    class="kirby-stats-os"
    :index="false"
    :columns="{
      name: {
        label: 'Operating System',
        type: 'kirby-stats-percent',
        mobile: true,
      },
      visits: { label: 'Visits', width: '8em', mobile: true },
    }"
    :rows="rows"
  />
</template>
