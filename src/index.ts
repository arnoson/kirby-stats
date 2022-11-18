import { kirbyup } from 'kirbyup/plugin'

declare global {
  interface Window {
    panel: any
  }
}

window.panel.plugin('arnoson/kirby-stats', {
  // @ts-ignore
  components: kirbyup.import('./(views|components)/*.vue'),
})
