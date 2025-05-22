<script setup lang="ts">
import { ref } from 'vue'
import { Stats, Type } from '../types'
import browserSection from '../components/browser-section.vue'
import pagesSection from '../components/pages-section.vue'
import osSection from '../components/os-section.vue'
import chartSection from '../components/chart-section.vue'
import dateNavigation from '../components/date-navigation.vue'

defineProps<{
  stats: Stats
  urls: Record<string, string>
  labels: Record<string, string>
  page: string
}>()

const type = ref<Type>('views')
</script>

<template>
  <k-panel-inside class="kirby-stats-main-view">
    <k-header>
      Stats {{ page ? ` for ${labels.page}` : '' }}
      <template #buttons>
        <date-navigation :urls="urls" :labels="labels" />
      </template>
    </k-header>

    <chart-section
      :stats="stats"
      :page="labels.page"
      :type="type"
      @update:type="type = $event"
    />

    <section class="k-section">
      <k-grid style="gap: 1.5rem">
        <k-column width="1/2">
          <section class="k-section" style="padding-bottom: 1.5rem">
            <header
              class="k-section-header"
              style="min-height: var(--height-xs)"
            >
              <k-headline>Devices</k-headline>
            </header>
            <browser-section :stats="stats" />
            <os-section :stats="stats" />
          </section>
        </k-column>
        <k-column width="1/2">
          <pages-section :stats="stats" :urls="urls" :type="type" />
        </k-column>
      </k-grid>
    </section>
  </k-panel-inside>
</template>

<style scoped>
.kirby-stats-main-view {
  .k-button[data-disabled] {
    opacity: 0.5;
    pointer-events: none;
    cursor: default;
  }

  .k-table {
    tr td:nth-child(2) {
      text-align: right;
    }

    tr th:nth-child(2) {
      text-align: center;
    }
  }
}
</style>
