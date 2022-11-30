/// <reference types="vite/client" />

import { kirbyup } from 'kirbyup/plugin'

declare global {
  interface Window {
    panel: any
    __VUE_DEVTOOLS_GLOBAL_HOOK__: { Vue: Vue }
  }
}

window.panel.plugin('arnoson/kirby-stats', {
  // @ts-ignore
  components: kirbyup.import('./(views|components)/*.vue'),
  use: {
    plugin(Vue: Vue) {
      if (import.meta.env.MODE === 'development') {
        window.__VUE_DEVTOOLS_GLOBAL_HOOK__.Vue = Vue
      }
    },
  },
})
