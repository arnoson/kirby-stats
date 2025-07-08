<script setup lang="ts">
defineProps<{
  value: string
  row: { percent: number; url?: string }
}>()
</script>

<template>
  <div class="kirby-stats-percent">
    <div class="kirby-stats-percent-wrapper">
      <div
        class="kirby-stats-percent-bar"
        :style="`width: ${row.percent}%`"
      ></div>
    </div>
    <div class="kirby-stats-percent-text k-text-field-preview">
      <k-link v-if="row.url" :to="row.url">{{ value }}</k-link>
      <span v-else>{{ value }}</span>
    </div>
  </div>
</template>

<style scoped>
.kirby-stats-percent {
  position: relative;
}

.kirby-stats-percent-text {
  /* Make sure text is above the bar. */
  position: relative;

  a:hover {
    text-decoration: underline;
  }
}

.kirby-stats-percent-bar {
  height: 100%;
  border-radius: var(--rounded);
  background-color: var(--kirby-stats-color-chart);
  opacity: 0.3;
}

tr:hover .kirby-stats-percent-bar {
  opacity: 0.43;
}

.kirby-stats-percent-wrapper {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  width: 100%;
  padding: 0.08rem 0.4rem;
}
</style>
