<template>
  <k-inside>
    <k-view class="k-stats-main-view">
      <k-header>
        Stats {{ page ? ` for ${labels.page}` : '' }}
        <template #left>
          <k-button-group>
            <k-button icon="cog">Settings</k-button>
          </k-button-group>
        </template>
        <template #right>
          <k-button-group>
            <k-button-link
              :link="urls.last"
              icon="angle-left"
              :disabled="!urls.last"
            />
            <k-dropdown>
              <k-button @click="toggleIntervalSelect" icon="calendar">
                {{ labels.date }}
              </k-button>
              <k-dropdown-content ref="intervalSelect">
                <k-button-link class="k-dropdown-item" :link="urls.today">
                  Today
                </k-button-link>
                <k-button-link class="k-dropdown-item" :link="urls['7-days']">
                  Last 7 days
                </k-button-link>
                <k-button-link class="k-dropdown-item" :link="urls['30-days']">
                  Last 30 days
                </k-button-link>
                <hr />
                <k-button-link class="k-dropdown-item" :link="urls.day">
                  Day
                </k-button-link>
                <k-button-link class="k-dropdown-item" :link="urls.week">
                  Week
                </k-button-link>
                <k-button-link class="k-dropdown-item" :link="urls.month">
                  Month
                </k-button-link>
                <k-button-link class="k-dropdown-item" :link="urls.year">
                  Year
                </k-button-link>
              </k-dropdown-content>
            </k-dropdown>
            <k-button-link
              icon="angle-right"
              :link="urls.next"
              :disabled="!urls.next"
            />
          </k-button-group>
        </template>
      </k-header>
      <section class="k-section">
        <header class="k-section-header">
          <k-button>
            <k-headline>Visits / Views</k-headline>
          </k-button>
        </header>
        <k-stats-chart :stats="stats" />
      </section>

      <k-grid gutter="medium">
        <k-column width="1/2">
          <section class="k-section" style="padding-bottom: 1.5rem">
            <header class="k-section-header">
              <k-headline>Devices</k-headline>
            </header>
            <k-stats-browsers :stats="stats" />
          </section>
          <section class="k-section">
            <k-stats-os :stats="stats" />
          </section>
        </k-column>
        <k-column width="1/2">
          <section class="k-section">
            <header class="k-section-header">
              <k-headline>Pages</k-headline>
            </header>
            <k-stats-pages :stats="stats" :urls="urls" />
          </section>
        </k-column>
      </k-grid>
    </k-view>
  </k-inside>
</template>

<script lang="ts">
import type { PropType } from 'vue'
import { Stats } from '../types'

export default {
  props: {
    stats: {
      type: Object as PropType<Stats>,
      required: true,
    },
    urls: {
      type: Object as PropType<Record<string, string>>,
      required: true,
    },
    labels: {
      type: Object as PropType<Record<string, string>>,
      required: true,
    },
    page: String,
  },

  methods: {
    toggleIntervalSelect() {
      // @ts-ignore
      this.$refs.intervalSelect?.toggle()
    },
  },
}
</script>

<style lang="scss">
.k-stats-main-view {
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
