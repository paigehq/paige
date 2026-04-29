<script setup lang="ts">
import type { PageEditProps } from '@/types/wiki'
import { router } from '@inertiajs/vue3'
import TiptapEditor from '@/components/editor/TiptapEditor.vue'

const { space, page, tree } = defineProps<PageEditProps>()

const saveUrl = `/s/${space.slug}/${page.slug}`

function onPublished() {
  router.visit(`/s/${space.slug}/${page.slug}`)
}
</script>

<template>
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 shrink-0 border-r border-gray-200 bg-white px-4 py-6">
      <div class="mb-4 flex items-center justify-between">
        <a
          :href="`/s/${space.slug}`"
          class="text-lg font-semibold text-gray-900 hover:text-violet-700"
        >{{ space.name }}</a>
      </div>
      <div class="mb-4 flex gap-2 text-xs">
        <a
          :href="`/s/${space.slug}/${page.slug}`"
          class="text-gray-500 hover:text-violet-700"
        >← View page</a>
        <span class="text-gray-300">|</span>
        <a
          :href="`/s/${space.slug}/${page.slug}/history`"
          class="text-gray-500 hover:text-violet-700"
        >History</a>
      </div>
      <nav>
        <ul class="space-y-1">
          <li v-for="node in tree" :key="node.id">
            <a
              :href="`/s/${space.slug}/${node.slug}`"
              class="block rounded px-3 py-1.5 text-sm"
              :class="[
                node.id === page.id
                  ? 'bg-violet-100 font-medium text-violet-700'
                  : 'text-gray-700 hover:bg-violet-50 hover:text-violet-700',
              ]"
            >{{ node.title }}</a>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Editor -->
    <main class="flex flex-1 flex-col overflow-hidden">
      <TiptapEditor
        :initial-title="page.title"
        :initial-content="page.content"
        :save-url="saveUrl"
        @published="onPublished"
      />
    </main>
  </div>
</template>
