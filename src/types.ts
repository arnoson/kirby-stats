export const browsers = [
  'Firefox',
  'Chrome',
  'Opera',
  'Edge',
  'Safari',
  'InternetExplorer',
] as const

export const os = ['Windows', 'Apple', 'Linux', 'Android', 'iOS'] as const

export type Browser = typeof browsers[number]

export type OS = typeof os[number]

export type StatsCounters = Record<'views' | 'visits' | Browser | OS, number>

export type Interval = 'hour' | 'day' | 'week' | 'month' | 'year'

export type StatsEntry = {
  time: number
  label: string
  missing?: boolean
  now?: boolean
  paths: Record<string, { title: string; counters: StatsCounters }>
}

export type Stats = Record<StatsEntry['time'], StatsEntry>
