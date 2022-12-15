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
          <k-headline>
            <button
              class="k-stats-type-button"
              :class="{ active: type === 'views' }"
              @click="type = 'views'"
            >
              <span class="k-stats-type-display">Views</span>
              <span class="k-stats-type-width">Views</span>
            </button>
            /
            <button
              class="k-stats-type-button"
              :class="{ active: type === 'visits' }"
              @click="type = 'visits'"
            >
              Visits
            </button>
          </k-headline>
        </header>
        <k-stats-chart :stats="stats" :type="type" :page="labels.page" />
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
            <k-stats-pages :stats="stats" :urls="urls" :type="type" />
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

  data() {
    return {
      type: 'views' as 'views' | 'visits',
    }
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

.k-stats {
  &-type-button {
    position: relative;

    // Adjust the letter-spacing to roughly match the bold weight.
    letter-spacing: 0.3px;

    &:not(.active):not(:hover) {
      color: var(--color-gray-600);
    }
    &.active {
      font-weight: var(--font-bold);
      letter-spacing: 0;
    }
  }

  // Make sure the buttons always takes up the same space by adding an invisible
  // bold version to avoid layout shift.
  &-type-display {
    position: absolute;
  }
  &-type-width {
    visibility: hidden;
    letter-spacing: 0;
    font-weight: var(--font-bold);
  }
}
</style>
