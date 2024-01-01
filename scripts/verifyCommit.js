// @ts-check
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'

const messagePath = resolve('.git/COMMIT_EDITMSG')
const message = readFileSync(messagePath, 'utf-8').trim()
const commitPattern =
  /^(revert: )?(feat|fix|docs|dx|style|refactor|perf|test|workflow|build|ci|chore|types|wip|release)(\(.+\))?: .{1,50}/

if (!commitPattern.test(message)) {
  console.error(
    '\nERROR: Proper commit message format is required for automated changelog generation. Examples:\n' +
      '- feat: add disableRoot option\n' +
      '- fix: handle keep-alive with aborted navigations (close #28)\n' +
      'See https://conventionalcommits.org for more details.\n',
  )
  process.exit(1)
}
