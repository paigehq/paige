<script setup lang="ts">
import type { TagIndexProps } from '@/types/wiki'
import { Link } from '@inertiajs/vue3'

const { tags } = defineProps<TagIndexProps>()
</script>

<template>
  <div class="min-h-screen bg-[#F7F5FF]">
    <header class="border-b border-gray-200 bg-white px-8 py-4">
      <div class="mx-auto max-w-3xl flex items-center justify-between">
        <h1 class="text-xl font-bold text-[#1A0B3B]">
          Tags
        </h1>
        <a href="/spaces" class="text-sm text-gray-500 hover:text-violet-700">← All spaces</a>
      </div>
    </header>

    <main class="mx-auto max-w-3xl px-8 py-8">
      <p v-if="tags.data.length === 0" class="text-sm text-gray-500">
        No tags yet. Tags are created when pages are saved with them.
      </p>

      <div v-else class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
        <Link
          v-for="tag in tags.data"
          :key="tag.id"
          :href="`/tags/${tag.slug}`"
          class="flex items-center justify-between px-5 py-3 hover:bg-[#F7F5FF]"
        >
          <span class="font-medium text-violet-700">{{ tag.name }}</span>
          <span class="text-sm text-gray-400">
            {{ tag.pagesCount }} {{ tag.pagesCount === 1 ? 'page' : 'pages' }}
          </span>
        </Link>
      </div>

      <!-- Pagination -->
      <div v-if="tags.last_page > 1" class="mt-6 flex justify-center gap-2 text-sm">
        <a
          v-if="tags.current_page > 1"
          :href="`/tags?page=${tags.current_page - 1}`"
          class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-gray-600 hover:border-violet-300"
        >← Previous</a>
        <span class="px-3 py-1.5 text-gray-500">
          Page {{ tags.current_page }} of {{ tags.last_page }}
        </span>
        <a
          v-if="tags.current_page < tags.last_page"
          :href="`/tags?page=${tags.current_page + 1}`"
          class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-gray-600 hover:border-violet-300"
        >Next →</a>
      </div>
    </main>
  </div>
</template>
