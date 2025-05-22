import mainView from './views/main-view.vue'
import percentCell from './components/percent-cell.vue'

declare global {
  interface Window {
    panel: {
      plugin(name: string, options: any): void
    }
  }
}

window.panel.plugin('arnoson/kirby-stats', {
  components: {
    'k-table-kirby-stats-percent-cell': percentCell,
    'kirby-stats-main-view': mainView,
  },
})
