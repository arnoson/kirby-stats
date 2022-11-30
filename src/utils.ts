export const slugifyPath = (str: string) =>
  (str.startsWith('/') ? str.slice(1) : str).replace('/', '+')
