<script setup lang="ts">
import { ref } from 'vue'
import { Stats, Type } from '../types'
import browserSection from '../components/browser-section.vue'
import pagesSection from '../components/pages-section.vue'
import osSection from '../components/os-section.vue'
import chartSection from '../components/chart-section.vue'

defineProps<{
  stats: Stats
  urls: Record<string, string>
  labels: Record<string, string>
  page: string
}>()

const type = ref<Type>('views')

const intervalSelect = ref(null)
const toggleIntervalSelect = () => {
  // @ts-ignore (missing types for component)
  intervalSelect.value?.toggle()
}
</script>

<template>
  <k-panel-inside class="kirby-stats-main-view">
    <k-header>
      Stats {{ page ? ` for ${labels.page}` : '' }}
      <template #buttons>
        <k-button-group>
          <k-button
            :link="urls.last"
            icon="angle-left"
            :disabled="!urls.last"
          />
          <k-button @click="toggleIntervalSelect" icon="calendar">
            {{ labels.date }}
          </k-button>
          <k-dropdown-content ref="intervalSelect">
            <k-button class="k-dropdown-item" :link="urls.today">
              Today
            </k-button>
            <k-button class="k-dropdown-item" :link="urls['7-days']">
              Last 7 days
            </k-button>
            <k-button class="k-dropdown-item" :link="urls['30-days']">
              Last 30 days
            </k-button>
            <hr />
            <k-button class="k-dropdown-item" :link="urls.day"> Day </k-button>
            <k-button class="k-dropdown-item" :link="urls.week">
              Week
            </k-button>
            <k-button class="k-dropdown-item" :link="urls.month">
              Month
            </k-button>
            <k-button class="k-dropdown-item" :link="urls.year">
              Year
            </k-button>
          </k-dropdown-content>
          <k-button
            icon="angle-right"
            :link="urls.next"
            :disabled="!urls.next"
          />
        </k-button-group>
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
            <header class="k-section-header">
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
