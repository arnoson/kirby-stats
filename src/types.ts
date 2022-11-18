export const browsers = [
  'Opera',
  'Edge',
  'InternetExplorer',
  'Firefox',
  'Safari',
  'Chrome',
] as const

export const os = ['Windows', 'Apple', 'Linux', 'Android', 'iOS'] as const

export type Browser = typeof browsers[number]

export type OS = typeof os[number]

export enum Interval {
  'hourly',
  'daily',
  'weekly',
  'monthly',
  'yearly',
}

export type StatsEntry = {
  Time: number
  Interval: Interval
  Name: string
  Synthetic?: boolean
} & Record<'Views' | 'Visits' | Browser | OS, number>

export type Stats = Record<StatsEntry['Time'], StatsEntry>
