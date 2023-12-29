;(() => {
  const events = ['click', 'scroll', 'keydown', 'mousemove', 'touchstart']
  const eventOptions = { once: true, passive: true }
  let statsAreSend = false

  const addEventListeners = () =>
    events.forEach((e) => document.addEventListener(e, sendStats, eventOptions))

  const removeEventListeners = () =>
    events.forEach((e) => document.addEventListener(e, sendStats))

  const sendStats = () => {
    if (statsAreSend) return

    const data = new FormData()
    data.append('path', location.pathname)
    const { referrer } = document
    if (referrer) data.append('referrer', new URL(referrer).host)
    navigator.sendBeacon('/stats/handle', data)

    removeEventListeners()
    statsAreSend = true
  }

  const isReload = performance.navigation.type === 1
  if (!isReload) addEventListeners()
})()
