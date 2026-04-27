import antfu from '@antfu/eslint-config'

export default antfu({
  ignores: [
    'boost.json',
    'pnpm-workspace.yaml',
    'CLAUDE.md',
  ],
})
