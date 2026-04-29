<script setup lang="ts">
import type { DiffViewProps } from '@/types/wiki'

const { space, page, revisionA, revisionB, diff } = defineProps<DiffViewProps>()

function lineClass(tag: string) {
  if (tag === 'insert')
    return 'bg-green-100 text-green-900'
  if (tag === 'delete')
    return 'bg-red-100 text-red-900'
  return 'bg-white text-gray-700'
}

function linePrefix(tag: string) {
  if (tag === 'insert')
    return '+'
  if (tag === 'delete')
    return '-'
  return ' '
}
</script>

<template>
  <div class="flex min-h-screen flex-col">
    <main class="mx-auto w-full max-w-4xl px-6 py-10">
      <nav class="mb-4 flex items-center gap-1 text-sm text-gray-500">
        <a :href="`/s/${space.slug}`" class="hover:text-violet-700">{{ space.name }}</a>
        <span class="text-gray-300">/</span>
        <a :href="`/s/${space.slug}/${page.slug}`" class="hover:text-violet-700">{{ page.title }}</a>
        <span class="text-gray-300">/</span>
        <a :href="`/s/${space.slug}/${page.slug}/history`" class="hover:text-violet-700">History</a>
        <span class="text-gray-300">/</span>
        <span class="text-gray-700">Diff</span>
      </nav>

      <h1 class="mb-2 text-2xl font-bold text-[#1A0B3B]">
        Comparing revisions
      </h1>
      <p class="mb-6 text-sm text-gray-500">
        Revision {{ revisionA.number }} ({{ revisionA.editorName }})
        → Revision {{ revisionB.number }} ({{ revisionB.editorName }})
      </p>

      <!-- Legend -->
      <div class="mb-4 flex gap-4 text-xs">
        <span class="flex items-center gap-1">
          <span class="inline-block h-3 w-3 rounded bg-red-200" />
          Removed
        </span>
        <span class="flex items-center gap-1">
          <span class="inline-block h-3 w-3 rounded bg-green-200" />
          Added
        </span>
      </div>

      <!-- Diff block -->
      <div class="overflow-x-auto rounded-lg border border-gray-200">
        <pre class="text-sm leading-6"><template
          v-for="(line, i) in diff" :key="i"
        ><div
          class="flex px-4"
          :class="[lineClass(line.tag)]"
        ><span class="mr-4 w-4 shrink-0 select-none font-mono text-gray-400">{{ linePrefix(line.tag) }}</span><span class="whitespace-pre-wrap break-all font-mono">{{ line.line }}</span></div></template></pre>
      </div>
    </main>
  </div>
</template>
