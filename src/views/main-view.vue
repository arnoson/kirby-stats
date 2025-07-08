<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Page, Stats, Type } from '../types'
import browserSection from '../components/browser-section.vue'
import pagesSection from '../components/pages-section.vue'
import osSection from '../components/os-section.vue'
import chartSection from '../components/chart-section.vue'
import dateNavigation from '../components/date-navigation.vue'

const props = defineProps<{
  stats: Stats
  urls: Record<string, string>
  labels: Record<string, string>
  page: Page
}>()

const selectedType = ref<Type>('visitors')
const type = computed(() => {
  // Visitors can only be stored on the whole site, not per page.
  if (props.page && selectedType.value === 'visitors') return 'visits'
  return selectedType.value
})
</script>

<template>
  <k-panel-inside class="kirby-stats-main-view">
    <k-header>
      Stats
      <template #buttons>
        <k-tag
          v-if="page"
          style="align-self: center"
          removable
          @remove="
            // @ts-ignore
            $go(props.urls.withoutPage)
          "
        >
          Page: <b>{{ page.title ?? page.id }}</b>
        </k-tag>
        <date-navigation :urls="urls" :labels="labels" />
      </template>
    </k-header>

    <div class="k-tabs" style="margin-bottom: var(--spacing-6)">
      <div class="k-tabs-tab" v-if="!page">
        <k-button
          variant="dimmed"
          class="k-tab-button"
          :current="type === 'visitors'"
          @click="selectedType = 'visitors'"
        >
          Visitors
        </k-button>
      </div>
      <div class="k-tabs-tab">
        <k-button
          variant="dimmed"
          class="k-tab-button"
          :current="type === 'visits'"
          @click="selectedType = 'visits'"
        >
          Visits
        </k-button>
      </div>
      <div class="k-tabs-tab">
        <k-button
          variant="dimmed"
          class="k-tab-button"
          :current="type === 'views'"
          @click="selectedType = 'views'"
        >
          Views
        </k-button>
      </div>
    </div>

    <chart-section :stats="stats" :page="page" :type="type" />

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
          <pages-section
            :stats="stats"
            :urls="urls"
            :type="type"
            :page="page"
            :labels="labels"
          />
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
