<template>
  <k-inside>
    <k-view>
      <k-header>Stats</k-header>
      <k-stats-chart :stats="stats" />
    </k-view>
  </k-inside>
</template>

<script>
export default {
  props: {
    stats: Array,
  },
}
</script>
