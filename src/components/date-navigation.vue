<script setup lang="ts">
import { ref } from 'vue'

defineProps<{ urls: Record<string, string>; labels: Record<string, string> }>()

const intervalSelect = ref(null)
const toggleIntervalSelect = () => {
  // @ts-ignore (missing types for component)
  intervalSelect.value?.toggle()
}
</script>

<template>
  <k-button-group>
    <k-button :link="urls.last" icon="angle-left" :disabled="!urls.last" />
    <k-button @click="toggleIntervalSelect" icon="calendar">
      {{ labels.date }}
    </k-button>
    <k-dropdown-content ref="intervalSelect">
      <k-button class="k-dropdown-item" :link="urls.today"> Today </k-button>
      <k-button class="k-dropdown-item" :link="urls['7-days']">
        Last 7 days
      </k-button>
      <k-button class="k-dropdown-item" :link="urls['30-days']">
        Last 30 days
      </k-button>
      <hr />
      <k-button class="k-dropdown-item" :link="urls.day"> Day </k-button>
      <k-button class="k-dropdown-item" :link="urls.week"> Week </k-button>
      <k-button class="k-dropdown-item" :link="urls.month"> Month </k-button>
      <k-button class="k-dropdown-item" :link="urls.year"> Year </k-button>
    </k-dropdown-content>
    <k-button icon="angle-right" :link="urls.next" :disabled="!urls.next" />
  </k-button-group>
</template>
