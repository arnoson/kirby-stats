<script setup lang="ts">
import { computed } from 'vue'
import { Stats } from '../types'
import { capitalize, slugifyPath } from '../utils'

const props = defineProps<{
  stats: Stats
  urls: Record<string, string>
  type: string
  page?: string
}>()

type Row = { name: string; count: number; percent: number; url: string }
const rows = computed(() => {
  let total = 0
  const data: Record<string, Row> = {}

  for (const { paths, missing } of Object.values(props.stats)) {
    if (missing) continue
    for (const [path, { counters, title }] of Object.entries(paths)) {
      const name = title || path
      const slug = slugifyPath(path)
      const url = props.urls.page.replace('{{slug}}', slug)
      data[path] ??= { name, count: 0, percent: 0, url }
      const value = props.type === 'views' ? counters.views : counters.visits
      data[path].count += value
      total += value
    }
  }

  return Object.values(data)
    .map((v) => ({
      ...v,
      percent: total === 0 ? 0 : (v.count / total) * 100,
    }))
    .filter((v) => !!v.count)
    .sort((a, b) => b.percent - a.percent)
})

const emptyMessage = computed(() =>
  props.page
    ? `No ${props.type} for ${props.page}`
    : `No ${props.type === 'views' ? 'viewed' : 'visited'} pages`,
)
</script>

<template>
  <k-table
    class="kirby-stats-pages"
    :index="false"
    :columns="{
      name: { label: 'Page', type: 'stats-page', mobile: true },
      count: { label: capitalize(type), width: '8em', mobile: true },
    }"
    :rows="rows"
    :empty="emptyMessage"
  />
</template>

<style scoped>
.kirby-stats-pages {
  .k-table-empty {
    padding: 0.325rem 0.75rem;
  }
}
</style>
