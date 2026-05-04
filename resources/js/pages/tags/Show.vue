<script setup lang="ts">
import type { TagShowProps } from '@/types/wiki'
import { Link } from '@inertiajs/vue3'

const { tag, pages } = defineProps<TagShowProps>()

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

// Group pages by space for display
interface SpaceGroup {
  spaceName: string
  spaceSlug: string
  pages: typeof pages.data
}

function groupBySpace(): SpaceGroup[] {
  const groups: Record<string, SpaceGroup> = {}

  for (const page of pages.data) {
    if (!groups[page.spaceSlug]) {
      groups[page.spaceSlug] = {
        spaceName: page.spaceName,
        spaceSlug: page.spaceSlug,
        pages: [],
      }
    }
    groups[page.spaceSlug].pages.push(page)
  }

  return Object.values(groups)
}
</script>

<template>
  <div class="min-h-screen bg-[#F7F5FF]">
    <header class="border-b border-gray-200 bg-white px-8 py-4">
      <div class="mx-auto max-w-3xl flex items-center gap-3">
        <a href="/tags" class="text-sm text-gray-500 hover:text-violet-700">← Tags</a>
        <span class="text-gray-300">/</span>
        <h1 class="text-xl font-bold text-[#1A0B3B]">
          {{ tag.name }}
        </h1>
        <span class="rounded-full bg-[#EDE7FF] px-2.5 py-0.5 text-xs font-medium text-violet-800">
          {{ pages.total }} {{ pages.total === 1 ? 'page' : 'pages' }}
        </span>
      </div>
    </header>

    <main class="mx-auto max-w-3xl px-8 py-8">
      <p v-if="pages.total === 0" class="text-sm text-gray-500">
        No published pages with this tag that you can access.
      </p>

      <template v-else>
        <div
          v-for="group in groupBySpace()"
          :key="group.spaceSlug"
          class="mb-8"
        >
          <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
            {{ group.spaceName }}
          </h2>
          <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
            <Link
              v-for="page in group.pages"
              :key="page.id"
              :href="`/s/${page.spaceSlug}/${page.slug}`"
              class="block px-5 py-4 hover:bg-[#F7F5FF]"
            >
              <p class="font-medium text-[#1A0B3B]">
                {{ page.title }}
              </p>
              <p class="mt-1 text-xs text-gray-400">
                {{ page.lastEditorName ? `Edited by ${page.lastEditorName} · ` : '' }}{{ formatDate(page.updatedAt) }}
              </p>
            </Link>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pages.last_page > 1" class="mt-6 flex justify-center gap-2 text-sm">
          <a
            v-if="pages.current_page > 1"
            :href="`/tags/${tag.slug}?page=${pages.current_page - 1}`"
            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-gray-600 hover:border-violet-300"
          >← Previous</a>
          <span class="px-3 py-1.5 text-gray-500">
            Page {{ pages.current_page }} of {{ pages.last_page }}
          </span>
          <a
            v-if="pages.current_page < pages.last_page"
            :href="`/tags/${tag.slug}?page=${pages.current_page + 1}`"
            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-gray-600 hover:border-violet-300"
          >Next →</a>
        </div>
      </template>
    </main>
  </div>
</template>
