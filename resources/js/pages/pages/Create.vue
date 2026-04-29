<script setup lang="ts">
import type { PageCreateProps } from '@/types/wiki'
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

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
  <AppLayout :space="space" :tree="tree" :current-page-id="null">
    <div class="flex flex-1 flex-col">
      <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-3">
        <h2 class="text-sm font-medium text-gray-600">
          New page in {{ space.name }}
        </h2>
        <button
          type="button"
          :disabled="form.processing || !form.title.trim()"
          class="rounded-lg bg-[#FF7043] px-4 py-1.5 text-sm font-medium text-white hover:bg-[#FF8A65] disabled:opacity-50"
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
        @keydown.enter="submitCreate"
      >

      <p v-if="form.errors.title" class="px-6 text-sm text-red-600">
        {{ form.errors.title }}
      </p>

      <p class="px-6 py-8 text-gray-400">
        Give the page a title above, then click "Create page" to open the full editor.
      </p>
    </div>
  </AppLayout>
</template>
