<script setup lang="ts">
import type { DiffViewProps } from '@/types/wiki'
import AppLayout from '@/layouts/AppLayout.vue'

const { space, page, tree, revisionA, revisionB, diff } = defineProps<DiffViewProps>()
</script>

<template>
  <AppLayout :space="space" :tree="tree" :current-page-id="page.id">
    <main class="flex-1 overflow-y-auto px-10 py-8">
      <nav class="mb-4 flex items-center gap-1 text-sm text-gray-500">
        <a :href="`/s/${space.slug}`" class="hover:text-violet-700">{{ space.name }}</a>
        <span class="text-gray-300">/</span>
        <a :href="`/s/${space.slug}/${page.slug}`" class="hover:text-violet-700">{{ page.title }}</a>
        <span class="text-gray-300">/</span>
        <a :href="`/s/${space.slug}/${page.slug}/history`" class="hover:text-violet-700">History</a>
        <span class="text-gray-300">/</span>
        <span class="text-gray-700">Diff {{ revisionA.number }} → {{ revisionB.number }}</span>
      </nav>

      <div class="mb-6">
        <h1 class="text-2xl font-bold text-[#1A0B3B]">
          {{ page.title }}
        </h1>
        <p class="mt-1 text-sm text-gray-500">
          Comparing revision {{ revisionA.number }} ({{ revisionA.editorName }})
          → revision {{ revisionB.number }} ({{ revisionB.editorName }})
        </p>
      </div>

      <div class="overflow-hidden rounded-lg border border-gray-200 bg-white font-mono text-sm">
        <div
          v-for="(line, i) in diff"
          :key="i"
          class="px-4 py-0.5"
          :class="{
            'bg-green-50 text-green-800': line.tag === 'insert',
            'bg-red-50 text-red-800': line.tag === 'delete',
            'text-gray-600': line.tag === 'equal',
          }"
        >
          <span class="mr-2 select-none opacity-40">
            {{ line.tag === 'insert' ? '+' : line.tag === 'delete' ? '-' : ' ' }}
          </span>{{ line.line }}
        </div>
      </div>
    </main>
  </AppLayout>
</template>
