<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

interface SearchResult {
  title: string
  excerpt: string
  spaceName: string
  spaceSlug: string
  pageSlug: string
  updatedAt: string
}

interface Props {
  query: string
  results: SearchResult[]
}

const props = defineProps<Props>()

const queryInput = ref(props.query)

function submit(): void {
  router.get('/search', { q: queryInput.value }, { preserveState: true })
}

interface SpaceGroup {
  spaceName: string
  spaceSlug: string
  results: SearchResult[]
}

const groupedResults = computed<SpaceGroup[]>(() => {
  const groups: Record<string, SpaceGroup> = {}

  for (const result of props.results) {
    if (!groups[result.spaceSlug]) {
      groups[result.spaceSlug] = {
        spaceName: result.spaceName,
        spaceSlug: result.spaceSlug,
        results: [],
      }
    }
    groups[result.spaceSlug].results.push(result)
  }

  return Object.values(groups)
})

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>

<template>
  <div class="min-h-screen bg-[#F7F5FF]">
    <header class="border-b border-gray-200 bg-white px-8 py-4">
      <div class="mx-auto max-w-3xl">
        <form class="flex gap-3" @submit.prevent="submit">
          <input
            v-model="queryInput"
            type="search"
            placeholder="Search pages…"
            autofocus
            class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-violet-500 focus:outline-none"
          >
          <button
            type="submit"
            class="rounded-lg bg-[#7C4DFF] px-4 py-2 text-sm font-medium text-white hover:bg-[#9B6FFF]"
          >
            Search
          </button>
        </form>
      </div>
    </header>

    <main class="mx-auto max-w-3xl px-8 py-8">
      <template v-if="query === ''">
        <p class="text-sm text-gray-500">
          Enter a query above to search across all spaces you can access.
        </p>
      </template>

      <template v-else-if="results.length === 0">
        <p class="text-sm text-gray-500">
          No results for <strong>{{ query }}</strong>.
        </p>
      </template>

      <template v-else>
        <p class="mb-6 text-sm text-gray-500">
          {{ results.length }} result{{ results.length === 1 ? '' : 's' }} for <strong>{{ query }}</strong>
        </p>

        <div
          v-for="group in groupedResults"
          :key="group.spaceSlug"
          class="mb-8"
        >
          <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
            {{ group.spaceName }}
          </h2>

          <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm divide-y divide-gray-100">
            <Link
              v-for="result in group.results"
              :key="result.pageSlug"
              :href="`/s/${result.spaceSlug}/${result.pageSlug}`"
              class="block px-5 py-4 hover:bg-[#F7F5FF]"
            >
              <p class="font-medium text-[#1A0B3B]">
                {{ result.title }}
              </p>
              <!-- eslint-disable-next-line vue/no-v-html -->
              <p
                v-if="result.excerpt"
                class="mt-1 text-sm text-gray-500 [&_mark]:bg-yellow-100 [&_mark]:text-[#1A0B3B] [&_mark]:rounded"
                v-html="result.excerpt"
              />
              <p class="mt-1 text-xs text-gray-400">
                Updated {{ formatDate(result.updatedAt) }}
              </p>
            </Link>
          </div>
        </div>
      </template>
    </main>
  </div>
</template>
