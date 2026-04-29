<script setup lang="ts">
import type { PageCreateProps } from '@/types/wiki'
import { useForm } from '@inertiajs/vue3'

const { space, tree } = defineProps<PageCreateProps>()

const form = useForm({
  title: '',
  content: null as string | null,
  parent_id: null as number | null,
})

function submitCreate() {
  form.post(`/s/${space.slug}/pages`)
}
</script>

<template>
  <div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-64 shrink-0 border-r border-gray-200 bg-white px-4 py-6">
      <div class="mb-4">
        <a
          :href="`/s/${space.slug}`"
          class="text-lg font-semibold text-gray-900 hover:text-violet-700"
        >{{ space.name }}</a>
      </div>
      <nav>
        <ul class="space-y-1">
          <li v-for="node in tree" :key="node.id">
            <a
              :href="`/s/${space.slug}/${node.slug}`"
              class="block rounded px-3 py-1.5 text-sm text-gray-700 hover:bg-violet-50 hover:text-violet-700"
            >{{ node.title }}</a>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Create form -->
    <main class="flex flex-1 flex-col">
      <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-3">
        <h2 class="text-sm font-medium text-gray-600">
          New page in {{ space.name }}
        </h2>
        <button
          type="button"
          class="rounded-lg bg-[#FF7043] px-4 py-1.5 text-sm font-medium text-white hover:bg-[#FF8A65]"
          @click="submitCreate"
        >
          Create page
        </button>
      </div>

      <input
        v-model="form.title"
        type="text"
        placeholder="Page title"
        class="border-b border-gray-100 px-6 py-4 text-3xl font-bold text-gray-900 outline-none placeholder:text-gray-300"
      >

      <p v-if="form.errors.title" class="px-6 text-sm text-red-600">
        {{ form.errors.title }}
      </p>

      <p class="px-6 py-8 text-gray-400">
        Give the page a title above, then click "Create page" to start editing in the full editor.
      </p>
    </main>
  </div>
</template>
