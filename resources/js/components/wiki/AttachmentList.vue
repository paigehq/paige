<script setup lang="ts">
import type { AttachmentItem } from '@/types/wiki'
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps<{
  attachments: AttachmentItem[]
  spaceSlug: string
  pageSlug: string
}>()

const isOpen = ref(props.attachments.length > 0)

function mimeIcon(mimeType: string): string {
  if (mimeType.startsWith('image/')) {
    return '🖼'
  }
  if (mimeType === 'application/pdf') {
    return '📄'
  }
  return '📎'
}

function deleteAttachment(attachment: AttachmentItem): void {
  router.delete(
    `/s/${props.spaceSlug}/${props.pageSlug}/attachments/${attachment.id}`,
    { preserveScroll: true },
  )
}
</script>

<template>
  <section class="mt-10 border-t border-gray-100 pt-6">
    <button
      type="button"
      class="flex w-full items-center gap-2 text-sm font-semibold text-gray-600 hover:text-gray-900"
      @click="isOpen = !isOpen"
    >
      <span class="text-xs text-gray-400">{{ isOpen ? '▼' : '▶' }}</span>
      Attachments ({{ attachments.length }})
    </button>

    <ul v-if="isOpen" class="mt-4 space-y-2">
      <li
        v-for="attachment in attachments"
        :key="attachment.id"
        class="flex items-center gap-3 rounded-lg border border-gray-100 bg-gray-50 px-4 py-2"
      >
        <img
          v-if="attachment.isImage && attachment.thumbnailUrl"
          :src="attachment.thumbnailUrl"
          :alt="attachment.filename"
          class="h-10 w-10 shrink-0 rounded object-cover"
        >
        <span v-else class="shrink-0 text-xl leading-none">{{ mimeIcon(attachment.mimeType) }}</span>

        <div class="min-w-0 flex-1">
          <a
            :href="attachment.downloadUrl"
            class="block truncate text-sm font-medium text-violet-700 hover:underline"
          >{{ attachment.filename }}</a>
          <p class="text-xs text-gray-400">
            {{ attachment.size }} · {{ attachment.mimeType }}
          </p>
        </div>

        <button
          v-if="attachment.canDelete"
          type="button"
          class="shrink-0 text-xs text-red-400 hover:text-red-600"
          @click="deleteAttachment(attachment)"
        >
          Delete
        </button>
      </li>

      <li v-if="attachments.length === 0" class="py-2 text-sm text-gray-400">
        No attachments yet.
      </li>
    </ul>
  </section>
</template>
