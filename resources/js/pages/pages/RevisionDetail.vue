<script setup lang="ts">
import type { RevisionDetailProps } from '@/types/wiki'

const { space, page, revision } = defineProps<RevisionDetailProps>()
</script>

<template>
  <div class="flex min-h-screen">
    <main class="mx-auto w-full max-w-3xl px-6 py-10">
      <nav class="mb-4 flex items-center gap-1 text-sm text-gray-500">
        <a :href="`/s/${space.slug}`" class="hover:text-violet-700">{{ space.name }}</a>
        <span class="text-gray-300">/</span>
        <a :href="`/s/${space.slug}/${page.slug}`" class="hover:text-violet-700">{{ page.title }}</a>
        <span class="text-gray-300">/</span>
        <a :href="`/s/${space.slug}/${page.slug}/history`" class="hover:text-violet-700">History</a>
        <span class="text-gray-300">/</span>
        <span class="text-gray-700">Revision {{ revision.number }}</span>
      </nav>

      <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        You are viewing a historical revision. This is read-only.
        <a :href="`/s/${space.slug}/${page.slug}`" class="ml-2 font-medium underline">View current version</a>
      </div>

      <h1 class="mb-2 text-3xl font-bold text-gray-900">
        {{ revision.title }}
      </h1>
      <p class="mb-8 text-sm text-gray-400">
        Revision {{ revision.number }} · saved by {{ revision.editorName }}
      </p>

      <!-- eslint-disable-next-line vue/no-v-html -->
      <div class="prose max-w-none" v-html="revision.html" />
    </main>
  </div>
</template>
