;(() => {
  const events = ['click', 'scroll', 'keydown', 'mousemove', 'touchstart']
  const eventOptions = { once: true, passive: true }
  let statsAreSend = false

  const addEventListeners = () =>
    events.forEach((e) => document.addEventListener(e, sendStats, eventOptions))

  const removeEventListeners = () =>
    events.forEach((e) =>
      document.removeEventListener(e, sendStats, eventOptions),
    )

  const sendStats = () => {
    if (statsAreSend) return

    const data = new FormData()
    data.append('path', location.pathname)
    const { referrer } = document
    if (referrer) data.append('referrer', new URL(referrer).host)
    navigator.sendBeacon('/kirby-stats/hit', data)

    removeEventListeners()
    statsAreSend = true
  }

  const isReload = window.performance
    .getEntriesByType('navigation')
    .some((entry) => entry.type === 'reload')

  if (!isReload) addEventListeners()
})()
