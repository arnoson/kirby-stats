<script setup lang="ts">
import { ref } from 'vue'
import { Urls } from '../types'
import { usePanel } from 'kirbyuse'

defineProps<{ urls: Urls; labels: Record<string, string> }>()

const panel = usePanel()
const intervalSelect = ref(null)
const toggleIntervalSelect = () => {
  // @ts-ignore (missing types for component)
  intervalSelect.value?.toggle()
}
</script>

<template>
  <k-button-group>
    <k-button @click="toggleIntervalSelect" icon="calendar">
      {{ labels.date }}
    </k-button>

    <k-dropdown-content ref="intervalSelect">
      <k-button
        v-for="(url, name) in urls.range"
        :key="name"
        class="k-dropdown-item"
        size="sm"
        :link="url"
        >{{ panel.t(`arnoson.kirby-stats.${name}`) }}</k-button
      >
      <hr />
      <k-button
        v-for="(url, name) in urls.interval"
        :key="name"
        class="k-dropdown-item"
        size="sm"
        :link="url"
        >{{ panel.t(`arnoson.kirby-stats.${name}`) }}</k-button
      >
    </k-dropdown-content>

    <k-prev-next
      :prev="urls.last && { link: urls.last }"
      :next="urls.next && { link: urls.next }"
    />
  </k-button-group>
</template>
