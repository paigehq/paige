<script setup lang="ts">
import type { SpaceIndexProps } from '@/types/wiki'
import { Link } from '@inertiajs/vue3'

const { spaces } = defineProps<SpaceIndexProps>()
</script>

<template>
  <div class="min-h-screen bg-[#F7F5FF]">
    <header class="border-b border-gray-200 bg-white px-8 py-4">
      <div class="mx-auto flex max-w-5xl items-center justify-between">
        <h1 class="text-xl font-bold text-[#1A0B3B]">
          Spaces
        </h1>
        <Link href="/" class="text-sm text-gray-500 hover:text-violet-700">
          Home
        </Link>
      </div>
    </header>

    <main class="mx-auto max-w-5xl px-8 py-10">
      <template v-if="spaces.length">
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <li
            v-for="space in spaces"
            :key="space.id"
            class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
          >
            <Link
              :href="`/s/${space.slug}`"
              class="block"
            >
              <div class="mb-1 flex items-center gap-2">
                <span class="text-base font-semibold text-[#1A0B3B] hover:text-violet-700">
                  {{ space.name }}
                </span>
                <span
                  v-if="space.visibility !== 'public'"
                  class="rounded-full bg-[#EDE7FF] px-2 py-0.5 text-xs font-medium text-violet-700"
                >
                  {{ space.visibility }}
                </span>
              </div>
              <p v-if="space.description" class="mt-1 text-sm text-gray-500">
                {{ space.description }}
              </p>
            </Link>
          </li>
        </ul>
      </template>

      <div v-else class="flex flex-col items-center py-24 text-center">
        <p class="text-gray-500">
          No spaces yet.
        </p>
      </div>
    </main>
  </div>
</template>
