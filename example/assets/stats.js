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

    const path = location.pathname.replace(/^\//, '')
    fetch(`/kirby-stats/page/${path}`, { keepalive: true })
    fetch(`/kirby-stats/site`, { keepalive: true })

    removeEventListeners()
    statsAreSend = true
  }

  const isReload = window.performance
    .getEntriesByType('navigation')
    .some((entry) => entry.type === 'reload')

  if (!isReload) addEventListeners()
})()
