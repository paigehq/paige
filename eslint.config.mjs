import antfu from '@antfu/eslint-config'

export default antfu({
  ignores: [
    '.mcp.json',
    'compose.yaml',
    'boost.json',
    'pnpm-workspace.yaml',
    'CLAUDE.md',
  ],
})
