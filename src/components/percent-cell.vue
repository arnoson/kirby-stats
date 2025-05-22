<script setup lang="ts">
defineProps<{
  value: string
  row: { percent: number }
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
      {{ value }}
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
}

.kirby-stats-percent-bar {
  height: 100%;
  border-radius: var(--rounded);
  background-color: light-dark(var(--color-blue-200), var(--color-blue-700));
}

tr:hover .kirby-stats-percent-bar {
  background-color: light-dark(var(--color-blue-300), var(--color-blue-750));
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
