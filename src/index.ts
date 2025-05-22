/// <reference types="vite/client" />

import mainView from './views/main-view.vue'
import percentCell from './components/percent-cell.vue'

declare global {
  interface Window {
    panel: any
    __VUE_DEVTOOLS_GLOBAL_HOOK__: { Vue: Vue }
  }
}

window.panel.plugin('arnoson/kirby-stats', {
  components: {
    'k-table-kirby-stats-percent-cell': percentCell,
    'kirby-stats-main-view': mainView,
  },
  use: {
    plugin(Vue: Vue) {
      if (import.meta.env.MODE === 'development') {
        window.__VUE_DEVTOOLS_GLOBAL_HOOK__.Vue = Vue
      }
    },
  },
})
