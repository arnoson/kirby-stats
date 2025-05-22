<script setup lang="ts">
import { computed, ref } from 'vue'
import { Stats } from '../types'
import { capitalize, slugifyPath } from '../utils'

const props = defineProps<{
  stats: Stats
  urls: Record<string, string>
  type: string
  page?: string
}>()

const isSearching = ref(false)
const searchQuery = ref('')

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

const filteredRows = computed(() =>
  isSearching
    ? rows.value.filter((row) => row.name.includes(searchQuery.value))
    : rows.value,
)

const pagination = ref({ page: 1, limit: 10 })

const paginatedRows = computed(() => {
  const { page, limit } = pagination.value
  const from = (page - 1) * limit
  const to = page * limit
  return filteredRows.value.slice(from, to)
})

const paginate = ({ page }: { page: number }) => {
  pagination.value.page = page
}

const emptyMessage = computed(() =>
  props.page
    ? `No ${props.type} for ${props.page}`
    : `No ${props.type === 'views' ? 'viewed' : 'visited'} pages`,
)
</script>

<template>
  <section class="k-section">
    <header class="k-section-header">
      <k-headline>Pages</k-headline>
      <k-button
        icon="filter"
        variant="filled"
        size="xs"
        @click="isSearching = !isSearching"
        >Filter</k-button
      >
    </header>
    <k-input
      v-if="isSearching"
      class="k-models-section-search"
      type="text"
      icon="search"
      placeholder="Filter …"
      autofocus
      v-model="searchQuery"
      @keydown.esc="isSearching = false"
    />
    <k-table
      class="kirby-stats-pages"
      :index="false"
      :columns="{
        name: { label: 'Page', type: 'stats-page', mobile: true },
        count: { label: capitalize(type), width: '8em', mobile: true },
      }"
      :rows="paginatedRows"
      :empty="emptyMessage"
      :pagination="{ ...pagination, details: true, total: filteredRows.length }"
      @paginate="paginate"
    />
  </section>
</template>

<style scoped>
.kirby-stats-pages {
  .k-table-empty {
    padding: 0.325rem 0.75rem;
  }
}
</style>
