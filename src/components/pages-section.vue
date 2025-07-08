<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Page, Stats, Type } from '../types'
import { usePanel } from 'kirbyuse'

const props = defineProps<{
  stats: Stats
  urls: Record<string, string>
  labels: Record<string, string>
  type: Type
  page?: Page
}>()

const { t } = usePanel()
const isSearching = ref(false)
const searchQuery = ref('')

// Pages only store visits and views. Visitors are defined as site visits,
// which we can't display here.
const type = computed(() => {
  if (props.type === 'visitors') return 'visits'
  return props.type
})

type Row = { name: string; count: number; percent: number; id: string }
const rows = computed<Row[]>(() => {
  const key = type.value
  const data = Object.values(props.stats.totalTraffic)
  const totalCount = Object.values(data).reduce((sum, v) => sum + v[key], 0)
  return data
    .filter((entry) => entry.uuid.startsWith('page://'))
    .map((entry) => ({
      ...entry,
      count: entry[key],
      percent: Math.round(totalCount ? (entry[key] / totalCount) * 100 : 0),
      url: props.urls.withPage?.replace('{{slug}}', toSlug(entry.id)),
    }))
    .sort((a, b) => b.percent - a.percent)
})

const filteredRows = computed(() => {
  const query = searchQuery.value.toLocaleLowerCase()
  return isSearching.value
    ? rows.value.filter((row) => row.name.toLocaleLowerCase().includes(query))
    : rows.value
})

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

const capitalize = (text: string) => text[0]?.toUpperCase() + text.slice(1)

const toSlug = (str: string) =>
  (str.startsWith('/') ? str.slice(1) : str).replace('/', '+')

watch(filteredRows, () => paginate({ page: 1 }))
</script>

<template>
  <section class="k-section">
    <header class="k-section-header">
      <k-headline>{{ t('pages') }}</k-headline>
      <k-button
        :disabled="rows.length <= 1"
        icon="filter"
        variant="filled"
        size="xs"
        @click="isSearching = !isSearching"
      >
        {{ t('filter') }}
      </k-button>
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
        name: { label: t('page'), type: 'kirby-stats-percent', mobile: true },
        count: { label: capitalize(type), width: '8em', mobile: true },
      }"
      :rows="paginatedRows"
      :pagination="{ ...pagination, details: true, total: filteredRows.length }"
      :empty="t('arnoson.kirby-stats.no-data')"
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
