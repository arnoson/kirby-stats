<template>
  <k-table
    class="k-stats-pages"
    :index="false"
    :columns="{
      name: { label: 'Page', type: 'stats-page', mobile: true },
      count: { label: capitalize(type), width: '8em', mobile: true },
    }"
    :rows="data"
    :empty="emptyMessage"
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
    type: { type: String, required: true },
    page: String,
  },

  computed: {
    data() {
      let total = 0
      const data = {} as Record<
        string,
        { name: string; count: number; percent: number; url: string }
      >

      for (const { paths, missing } of Object.values(this.stats)) {
        if (missing) continue
        for (const [path, { counters, title }] of Object.entries(paths)) {
          const name = title || path
          const slug = slugifyPath(path)
          const url = this.urls.page.replace('{{slug}}', slug)
          data[path] ??= { name, count: 0, percent: 0, url }
          const value = this.type === 'views' ? counters.views : counters.visits
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
    },

    emptyMessage() {
      return this.page
        ? `No ${this.type} for ${this.page}`
        : `No ${this.type === 'views' ? 'viewed' : 'visited'} pages`
    },
  },

  methods: {
    capitalize(text: string) {
      return text[0].toUpperCase() + text.slice(1)
    },
  },
}
</script>

<style>
.k-stats-pages {
  & .k-table-empty {
    padding: 0.325rem 0.75rem;
  }
}
</style>
