export const slugifyPath = (str: string) =>
  (str.startsWith('/') ? str.slice(1) : str).replace('/', '+')

export const pascalToTitle = (str: string) => str.replace(/([A-Z])/g, " $1");