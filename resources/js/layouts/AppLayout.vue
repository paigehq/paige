<script setup lang="ts">
import type { SpaceData, TreeNode } from '@/types/wiki'
import { router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import PageTree from '@/components/wiki/PageTree.vue'

defineProps<{
  space: SpaceData
  tree: TreeNode[]
  currentPageId?: number | null
}>()

const page = usePage()
const authUser = computed(() => (page.props.auth as { user: { name: string, email: string } | null }).user)

function logout(): void {
  router.post('/logout')
}
</script>

<template>
  <div class="flex min-h-screen bg-[#F7F5FF]">
    <!-- Sidebar -->
    <aside class="flex w-64 shrink-0 flex-col border-r border-gray-200 bg-white">
      <!-- Space header -->
      <div class="border-b border-gray-100 px-4 py-4">
        <a
          :href="`/s/${space.slug}`"
          class="block text-base font-semibold text-[#1A0B3B] hover:text-violet-700"
        >
          {{ space.name }}
        </a>
        <p v-if="space.description" class="mt-0.5 truncate text-xs text-gray-400">
          {{ space.description }}
        </p>
      </div>

      <!-- New page button -->
      <div class="border-b border-gray-100 px-3 py-2">
        <a
          :href="`/s/${space.slug}/new`"
          class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-[#FF7043] hover:bg-orange-50"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          New page
        </a>
      </div>

      <!-- Page tree -->
      <nav class="flex-1 overflow-y-auto px-2 py-3">
        <PageTree
          :nodes="tree"
          :space-slug="space.slug"
          :current-page-id="currentPageId"
          :depth="0"
        />
      </nav>

      <!-- Auth strip -->
      <div class="border-t border-gray-100 px-4 py-3">
        <template v-if="authUser">
          <p class="truncate text-xs font-medium text-gray-700">
            {{ authUser.name }}
          </p>
          <p class="mb-2 truncate text-xs text-gray-400">
            {{ authUser.email }}
          </p>
          <button
            type="button"
            class="text-xs text-gray-500 hover:text-violet-700"
            @click="logout"
          >
            Sign out
          </button>
        </template>
        <template v-else>
          <a href="/login" class="text-xs text-violet-700 hover:underline">Sign in</a>
        </template>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex flex-1 flex-col overflow-hidden">
      <slot />
    </div>
  </div>
</template>
