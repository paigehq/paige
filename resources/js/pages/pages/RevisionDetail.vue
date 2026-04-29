<script setup lang="ts">
import type { PageHistoryProps } from '@/types/wiki'
import AppLayout from '@/layouts/AppLayout.vue'

const { space, page, tree, revisions } = defineProps<PageHistoryProps>()

function diffUrl(a: number, b: number) {
  return `/s/${space.slug}/${page.slug}/history/${a}/diff/${b}`
}
</script>

<template>
  <AppLayout :space="space" :tree="tree" :current-page-id="page.id">
    <main class="flex-1 overflow-y-auto px-10 py-8">
      <nav class="mb-4 flex items-center gap-1 text-sm text-gray-500">
        <a :href="`/s/${space.slug}`" class="hover:text-violet-700">{{ space.name }}</a>
        <span class="text-gray-300">/</span>
        <a :href="`/s/${space.slug}/${page.slug}`" class="hover:text-violet-700">{{ page.title }}</a>
        <span class="text-gray-300">/</span>
        <span class="text-gray-700">History</span>
      </nav>

      <h1 class="mb-6 text-2xl font-bold text-[#1A0B3B]">
        Revision history
      </h1>

      <div class="space-y-2">
        <div
          v-for="(rev, i) in revisions"
          :key="rev.number"
          class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-5 py-4"
        >
          <div>
            <span class="text-sm font-medium text-gray-800">Revision {{ rev.number }}</span>
            <span class="ml-2 text-xs text-gray-400">by {{ rev.editorName }}</span>
            <p v-if="rev.changeSummary" class="mt-0.5 text-xs text-gray-500">
              {{ rev.changeSummary }}
            </p>
          </div>
          <div class="flex items-center gap-3 text-sm">
            <a
              :href="`/s/${space.slug}/${page.slug}/history/${rev.number}`"
              class="text-violet-700 hover:underline"
            >View</a>
            <a
              v-if="i < revisions.length - 1"
              :href="diffUrl(revisions[i + 1].number, rev.number)"
              class="text-gray-500 hover:text-violet-700"
            >Diff ↑</a>
          </div>
        </div>
      </div>
    </main>
  </AppLayout>
</template>
