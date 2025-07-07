'use strict'
;(() => {
  const n = ['click', 'scroll', 'keydown', 'mousemove', 'touchstart'],
    s = { once: !0, passive: !0 }
  let t = !1
  const a = () => n.forEach((e) => document.addEventListener(e, o, s)),
    r = () => n.forEach((e) => document.removeEventListener(e, o, s)),
    o = () => {
      if (t) return
      const e = location.pathname.replace(/^\//, '')
      fetch(`/kirby-stats/page/${e}`, { keepalive: !0 }),
        fetch('/kirby-stats/site', { keepalive: !0 }),
        r(),
        (t = !0)
    },
    i = window.performance
      .getEntriesByType('navigation')
      .some((e) => e.type === 'reload')
  i || a()
})()
