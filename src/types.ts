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

export type Traffic = Record<
  number,
  {
    label: string
    views: number
    visits: number
    visitors: number
    unfinished: boolean
  }
>

export type Stats = {
  traffic: Traffic
  totalTraffic: {
    name: string
    id: string
    views: number
    visits: number
  }[]
  meta: {
    browser: Record<string, number>
    os: Record<string, number>
  }
}

export type Page = {
  title: string
  uuid?: string
  id: string
}
