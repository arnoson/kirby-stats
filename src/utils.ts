export const slugifyPath = (str: string) =>
  (str.startsWith('/') ? str.slice(1) : str).replace('/', '+')

export const pascalToTitle = (str: string) => str.replace(/([A-Z])/g, ' $1')

export const capitalize = (text: string) =>
  text[0].toUpperCase() + text.slice(1)
