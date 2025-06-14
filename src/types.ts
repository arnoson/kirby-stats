export const browsers = [
  'Firefox',
  'Chrome',
  'Opera',
  'Safari',
  'MicrosoftEdge',
  'InternetExplorer',
] as const

export const os = ['Windows', 'Mac', 'Linux', 'Android', 'iOS'] as const

export type Browser = (typeof browsers)[number]

export type OS = (typeof os)[number]

export type Type = 'visitors' | 'visits' | 'views'

export type StatsCounters = Record<'visits' | 'views' | Browser | OS, number>

export type Interval = 'hour' | 'day' | 'week' | 'month' | 'year'

export type StatsEntry = {
  traffic: Record<
    number,
    | { missing: true; label: string }
    | {
        missing: undefined
        visits: number
        views: number
        label: string
        unfinished: boolean
      }
  >
  meta: {
    browser: Record<string, number>
    os: Record<string, number>
  }
}

export type Stats = Record<string, StatsEntry>

export type Page = {
  title: string
  uuid?: string
  id: string
}
