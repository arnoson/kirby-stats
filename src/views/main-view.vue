<script setup lang="ts">
import { usePanel } from 'kirbyuse'
import { computed, ref } from 'vue'
import browserSection from '../components/browser-section.vue'
import chartSection from '../components/chart-section.vue'
import dateNavigation from '../components/date-navigation.vue'
import osSection from '../components/os-section.vue'
import pagesSection from '../components/pages-section.vue'
import { Page, Stats, Type, Urls } from '../types'

const props = defineProps<{
  stats: Stats
  urls: Urls
  labels: Record<string, string>
  page: Page
}>()

const { t } = usePanel()
const selectedType = ref<Type>('visitors')
const type = computed(() => {
  // Visitors can only be stored on the whole site, not per page.
  if (props.page && selectedType.value === 'visitors') return 'visits'
  return selectedType.value
})
const isSite = computed(() => !props.page)
const panel = usePanel()

// We could use the user locale here, but some languages don't support compact
// number representation. E.g. in german 1300 is still 1300 instead of 1.3k.
// Other analytic tools use the english compact notation even in the german
// translation so we'll do the same.
const compactNumber = (n: number) =>
  new Intl.NumberFormat('en', {
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(n)

const total = computed(() => {
  const uuid = props.page?.uuid ?? 'site://'
  const traffic = props.stats.totalTraffic[uuid]
  return {
    views: compactNumber(traffic?.views ?? 0),
    visits: compactNumber(traffic?.visits ?? 0),
    visitors: compactNumber(traffic?.visitors ?? 0),
  }
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

    <div class="kirby-stats-tabs">
      <div class="k-tabs" style="margin-bottom: var(--spacing-6)">
        <div class="k-tabs-tab" v-if="!page">
          <k-button
            variant="dimmed"
            class="k-tabs-button"
            :current="type === 'visitors'"
            @click="selectedType = 'visitors'"
          >
            {{ panel.t('arnoson.kirby-stats.visitors') }}
          </k-button>
        </div>
        <div class="k-tabs-tab">
          <k-button
            variant="dimmed"
            class="k-tabs-button"
            :current="type === 'visits'"
            @click="selectedType = 'visits'"
          >
            {{ panel.t('arnoson.kirby-stats.visits') }}
          </k-button>
        </div>
        <div class="k-tabs-tab">
          <k-button
            variant="dimmed"
            class="k-tabs-button"
            :current="type === 'views'"
            @click="selectedType = 'views'"
          >
            {{ panel.t('arnoson.kirby-stats.views') }}
          </k-button>
        </div>
      </div>
      <div class="kirby-stats-total">
        <k-box
          v-if="isSite"
          theme="info"
          :html="true"
          :data-show="type === 'visitors'"
          :text="
            panel.t('arnoson.kirby-stats.total-visitors', {
              visitors: total.visitors,
            })
          "
        />
        <k-box
          theme="info"
          :html="true"
          :data-show="type === 'visits'"
          :text="
            panel.t('arnoson.kirby-stats.total-visits', {
              visits: total.visits,
            })
          "
        />
        <k-box
          theme="info"
          :html="true"
          :data-show="type === 'views'"
          :text="
            panel.t('arnoson.kirby-stats.total-views', {
              views: total.views,
            })
          "
        />
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
              <k-headline>{{ t('arnoson.kirby-stats.devices') }}</k-headline>
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
  /* Color tints in increments of 50 are only available since Kirby 5 */
  --kirby-stats-color-chart: light-dark(
    var(--color-blue),
    var(--color-blue-650, var(--color-blue-700))
  );

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

.kirby-stats-tabs {
  display: flex;
  align-items: start;
  justify-content: space-between;
  gap: 1rem;
}

/* To prevent the boxes from jumping when changing the type, we stack them
all on top of each other and only hide them. This way each box takes up the
width of the largest box. */
.kirby-stats-total {
  display: grid;

  > * {
    grid-area: 1/1;
    justify-content: center;
  }

  > *:not([data-show]) {
    visibility: hidden;
  }
}
</style>
