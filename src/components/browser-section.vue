<script setup lang="ts">
import { computed } from 'vue'
import { Stats } from '../types'
import { usePanel } from 'kirbyuse'

const props = defineProps<{ stats: Stats }>()
const { t } = usePanel()

type Row = { name: string; visits: number; percent: number }
const rows = computed<Row[]>(() => {
  const data = props.stats.meta.browser
  const totalVisits = Object.values(data).reduce((sum, v) => sum + v, 0)
  return Object.entries(data)
    .map(([name, visits]) => ({
      name,
      visits,
      percent: Math.round(totalVisits ? (visits / totalVisits) * 100 : 0),
    }))
    .sort((a, b) => b.percent - a.percent)
})
</script>

<template>
  <section class="k-section">
    <k-table
      class="k-stats-browsers"
      :index="false"
      :columns="{
        name: {
          label: t('arnoson.kirby-stats.browser'),
          type: 'kirby-stats-percent',
          mobile: true,
        },
        visits: {
          label: t('arnoson.kirby-stats.visits'),
          width: '8em',
          mobile: true,
        },
      }"
      :rows="rows"
      :empty="t('arnoson.kirby-stats.no-data')"
    />
  </section>
</template>
