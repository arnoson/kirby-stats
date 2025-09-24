const events = ['click', 'scroll', 'keydown', 'mousemove', 'touchstart']
const eventOptions: AddEventListenerOptions = { once: true, passive: true }

let initialStatsAreSend = false
const handleEvent = () => {
  if (initialStatsAreSend) return
  sendStats()
  initialStatsAreSend = true
}

const addEventListeners = () =>
  events.forEach((e) => document.addEventListener(e, handleEvent, eventOptions))

const removeEventListeners = () =>
  events.forEach((e) =>
    document.removeEventListener(e, sendStats, eventOptions),
  )

const sendStats = () => {
  console.log('send stats')
  const path = location.pathname.replace(/^\//, '')
  fetch(`/kirby-stats/page/${path}`, { keepalive: true })
  fetch(`/kirby-stats/site`, { keepalive: true })
  removeEventListeners()
}

const isReload = window.performance
  .getEntriesByType('navigation')
  .some((entry) => (entry as PerformanceNavigationTiming).type === 'reload')

if (!isReload) addEventListeners()

// Listen to push state for SPAs or ajax navigation.
type PushState = typeof window.history.pushState
window.history.pushState = new Proxy(window.history.pushState, {
  apply(
    target: PushState,
    thisArg: History,
    args: Parameters<PushState>,
  ): ReturnType<PushState> {
    const result = target.apply(thisArg, args)
    sendStats()
    return result
  },
})
