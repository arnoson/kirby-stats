<template>
  <k-inside>
    <k-view class="k-stats-main-view">
      <k-header>
        Stats {{ page ? `/${page}` : '' }}
        <template #left>
          <k-button-group>
            <k-button icon="cog">Settings</k-button>
          </k-button-group>
        </template>
        <template #right>
          <k-button-group>
            <k-button-link
              :link="urls.lastInterval"
              icon="angle-left"
              :disabled="!urls.lastInterval"
            />
            <k-dropdown>
              <k-button @click="toggleIntervalSelect" icon="calendar">{{
                dateLabel
              }}</k-button>
              <k-dropdown-content ref="periodSelect">
                <k-dropdown-item @click="selectRange('today')"
                  >Today</k-dropdown-item
                >
                <k-dropdown-item @click="selectRange('7-days')"
                  >Last 7 days</k-dropdown-item
                >
                <k-dropdown-item @click="selectRange('30-days')"
                  >Last 30 days</k-dropdown-item
                >
                <hr />
                <k-dropdown-item @click="selectInterval('day')"
                  >Day</k-dropdown-item
                >
                <k-dropdown-item @click="selectInterval('week')"
                  >Week</k-dropdown-item
                >
                <k-dropdown-item @click="selectInterval('month')"
                  >Month</k-dropdown-item
                >
                <k-dropdown-item @click="selectInterval('year')"
                  >Year</k-dropdown-item
                >
              </k-dropdown-content>
            </k-dropdown>
            <k-button-link
              icon="angle-right"
              :link="urls.nextInterval"
              :disabled="!urls.nextInterval"
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
            <k-stats-pages :stats="stats" />
          </section>
        </k-column>
      </k-grid>
    </k-view>
  </k-inside>
</template>

<script lang="ts">
import { Interval } from '../types'

export default {
  props: {
    stats: Array,
    page: String,
    from: String,
    to: String,
    urls: { type: Array, required: true },
    dateLabel: String,
  },

  methods: {
    selectRange(range: 'today' | '7-days' | '30-days') {
      // @ts-ignore
      this.$go(`stats/${range}`)
    },

    selectInterval(interval: Interval) {
      // @ts-ignore
      this.$go(this.urls[`${interval}Interval`])
    },

    toggleIntervalSelect() {
      // @ts-ignore
      this.$refs.periodSelect?.toggle()
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
