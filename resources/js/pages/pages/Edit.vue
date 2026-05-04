<script setup lang="ts">
import type { PageEditProps } from '@/types/wiki'
import { router, useForm } from '@inertiajs/vue3'
import TiptapEditor from '@/components/editor/TiptapEditor.vue'
import TagInput from '@/components/wiki/TagInput.vue'
import AppLayout from '@/layouts/AppLayout.vue'

const { space, page, tree } = defineProps<PageEditProps>()

const saveUrl = `/s/${space.slug}/${page.slug}`

const form = useForm({
  title: page.title,
  content: page.content ?? '',
  action: 'draft' as 'draft' | 'publish',
  change_summary: '',
  tags: page.tags,
})
function onPublished() {
  router.visit(`/s/${space.slug}/${page.slug}`)
}
</script>

<template>
  <AppLayout :space="space" :tree="tree" :current-page-id="page.id">
    <div class="flex flex-1 flex-col overflow-hidden">
      <!-- Editor breadcrumb strip -->
      <div class="flex items-center gap-3 border-b border-gray-200 bg-white px-6 py-2 text-sm text-gray-500">
        <a :href="`/s/${space.slug}/${page.slug}`" class="hover:text-violet-700">← View page</a>
        <span class="text-gray-300">|</span>
        <a :href="`/s/${space.slug}/${page.slug}/history`" class="hover:text-violet-700">History</a>
      </div>
      <TiptapEditor
        :initial-title="page.title"
        :initial-content="page.content"
        :save-url="saveUrl"
        :space-slug="space.slug"
        :page-slug="page.slug"
        @published="onPublished"
      />

      <!-- Tag input below editor -->
      <div class="mt-6">
        <TagInput v-model="form.tags" />
      </div>
    </div>
  </AppLayout>
</template>
