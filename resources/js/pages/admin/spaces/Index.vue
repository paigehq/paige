<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import ConfirmModal from '@/components/ConfirmModal.vue'

interface SpaceRow {
  id: number
  name: string
  slug: string
  description: string | null
  visibility: string
  archived: boolean
  createdAt: string
}

interface PaginatedSpaces {
  data: SpaceRow[]
  current_page: number
  last_page: number
  next_page_url: string | null
  prev_page_url: string | null
}

const { spaces } = defineProps<{ spaces: PaginatedSpaces }>()

const pendingArchiveId = ref<number | null>(null)

function confirmArchive(): void {
  if (pendingArchiveId.value === null) {
    return
  }
  router.delete(`/admin/spaces/${pendingArchiveId.value}`)
  pendingArchiveId.value = null
}
</script>

<template>
  <div class="min-h-screen bg-[#F7F5FF]">
    <header class="border-b border-gray-200 bg-white px-8 py-4">
      <div class="mx-auto flex max-w-5xl items-center justify-between">
        <h1 class="text-xl font-bold text-[#1A0B3B]">
          Admin — Spaces
        </h1>
        <Link
          href="/admin/spaces/create"
          class="rounded-lg bg-[#FF7043] px-4 py-2 text-sm font-medium text-white hover:bg-[#FF8A65]"
        >
          New Space
        </Link>
      </div>
    </header>

    <main class="mx-auto max-w-5xl px-8 py-8">
      <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
          <thead class="border-b border-gray-100 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
            <tr>
              <th class="px-5 py-3">
                Name
              </th>
              <th class="px-5 py-3">
                Slug
              </th>
              <th class="px-5 py-3">
                Visibility
              </th>
              <th class="px-5 py-3">
                Status
              </th>
              <th class="px-5 py-3" />
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="space in spaces.data"
              :key="space.id"
              :class="{ 'opacity-50': space.archived }"
            >
              <td class="px-5 py-3 font-medium text-[#1A0B3B]">
                {{ space.name }}
              </td>
              <td class="px-5 py-3 text-gray-500">
                {{ space.slug }}
              </td>
              <td class="px-5 py-3">
                <span class="rounded-full bg-[#EDE7FF] px-2 py-0.5 text-xs font-medium text-violet-700">
                  {{ space.visibility }}
                </span>
              </td>
              <td class="px-5 py-3">
                <span
                  v-if="space.archived"
                  class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500"
                >
                  Archived
                </span>
              </td>
              <td class="px-5 py-3 text-right">
                <div v-if="!space.archived" class="flex justify-end gap-3">
                  <Link
                    :href="`/admin/spaces/${space.id}/edit`"
                    class="text-violet-700 hover:underline"
                  >
                    Edit
                  </Link>
                  <button
                    type="button"
                    class="text-red-500 hover:underline"
                    @click="pendingArchiveId = space.id"
                  >
                    Archive
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="spaces.last_page > 1" class="mt-6 flex justify-center gap-3">
        <Link
          v-if="spaces.prev_page_url"
          :href="spaces.prev_page_url"
          class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm hover:bg-gray-50"
        >
          Previous
        </Link>
        <Link
          v-if="spaces.next_page_url"
          :href="spaces.next_page_url"
          class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm hover:bg-gray-50"
        >
          Next
        </Link>
      </div>
    </main>

    <ConfirmModal
      v-if="pendingArchiveId !== null"
      title="Archive this space?"
      message="Members will lose access until it is restored. Existing pages are preserved."
      confirm-label="Archive"
      :dangerous="true"
      @confirm="confirmArchive"
      @cancel="pendingArchiveId = null"
    />
  </div>
</template>
