<template>
  <div class="k-table-stats-percent-cell">
    <div class="k-table-stats-percent-cell-bar-wrapper">
      <div
        class="k-table-stats-percent-cell-bar"
        :style="`width: ${row.percent}%`"
      ></div>
    </div>
    <a :href="row.url">
      <div class="k-table-stats-percent-cell-text k-text-field-preview">
        {{ value }}
      </div>
    </a>
  </div>
</template>

<script lang="ts">
export default {
  props: {
    value: { type: String, required: true },
    row: { type: Object, required: true },
  },
}
</script>

<style>
.k-table-stats-percent-cell {
  position: relative;
}

.k-table-stats-percent-cell-text {
  /* Make sure text is above the bar. */
  position: relative;
}

.k-table-stats-percent-cell-bar {
  height: 100%;
  border-radius: var(--rounded);
  background-color: var(--color-blue-200);
}

.k-table-stats-percent-cell-bar-wrapper {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  width: 100%;
  padding: 0.08rem 0.4rem;
}
</style>
