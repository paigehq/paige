<script setup lang="ts">
import type { TreeNode } from '@/types/wiki'
import { onMounted, ref } from 'vue'

const props = defineProps<{
  nodes: TreeNode[]
  spaceSlug: string
  currentPageId?: number | null
  depth?: number
}>()

const storageKey = `paige:tree:${props.spaceSlug}`

function loadExpanded(): Set<number> {
  try {
    const raw = localStorage.getItem(storageKey)
    return raw ? new Set(JSON.parse(raw) as number[]) : new Set()
  }
  catch {
    return new Set()
  }
}

function saveExpanded(ids: Set<number>): void {
  localStorage.setItem(storageKey, JSON.stringify([...ids]))
}

const expanded = ref<Set<number>>(new Set())

onMounted(() => {
  expanded.value = loadExpanded()
})

function toggle(id: number): void {
  const next = new Set(expanded.value)
  if (next.has(id)) {
    next.delete(id)
  }
  else {
    next.add(id)
  }
  expanded.value = next
  saveExpanded(next)
}

function isExpanded(id: number): boolean {
  return expanded.value.has(id)
}

function isCurrent(id: number): boolean {
  return props.currentPageId === id
}
</script>

<template>
  <ul :class="depth ? 'ml-3 border-l border-gray-100' : ''" class="space-y-0.5">
    <li v-for="node in nodes" :key="node.id">
      <div class="flex items-center gap-1">
        <!-- Expand/collapse toggle -->
        <button
          v-if="node.children.length"
          type="button"
          class="flex h-5 w-5 shrink-0 items-center justify-center rounded text-gray-400 hover:text-gray-600"
          :aria-label="isExpanded(node.id) ? 'Collapse' : 'Expand'"
          @click.stop="toggle(node.id)"
        >
          <svg class="h-3 w-3 transition-transform" :class="isExpanded(node.id) ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
        <!-- Spacer when no children -->
        <span v-else class="w-5 shrink-0" />

        <a
          :href="`/s/${spaceSlug}/${node.slug}`"
          class="flex min-w-0 flex-1 items-center gap-1.5 rounded px-2 py-1 text-sm"
          :class="[
            isCurrent(node.id)
              ? 'bg-violet-100 font-medium text-violet-700'
              : 'text-gray-700 hover:bg-violet-50 hover:text-violet-700',
          ]"
        >
          <span class="truncate">{{ node.title }}</span>
          <span
            v-if="node.isDraft"
            class="shrink-0 rounded bg-amber-100 px-1 py-0.5 text-[10px] font-medium leading-none text-amber-700"
          >draft</span>
        </a>
      </div>

      <!-- Recursive children -->
      <PageTree
        v-if="node.children.length && isExpanded(node.id)"
        :nodes="node.children"
        :space-slug="spaceSlug"
        :current-page-id="currentPageId"
        :depth="(depth ?? 0) + 1"
      />
    </li>
  </ul>
</template>
