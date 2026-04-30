<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3'

interface SpaceEditData {
  id: number
  name: string
  slug: string
  description: string | null
  visibility: 'public' | 'private' | 'secret'
}

const { space } = defineProps<{ space: SpaceEditData }>()

const form = useForm({
  name: space.name,
  slug: space.slug,
  description: space.description ?? '',
  visibility: space.visibility,
})

function submit(): void {
  form.put(`/admin/spaces/${space.id}`)
}

function archiveSpace(): void {
  if (confirm('Archive this space? Members will lose access until it is restored.')) {
    router.delete(`/admin/spaces/${space.id}`)
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#F7F5FF]">
    <header class="border-b border-gray-200 bg-white px-8 py-4">
      <div class="mx-auto flex max-w-2xl items-center gap-4">
        <Link href="/admin/spaces" class="text-sm text-gray-500 hover:text-violet-700">
          ← Spaces
        </Link>
        <h1 class="text-xl font-bold text-[#1A0B3B]">
          Edit Space
        </h1>
      </div>
    </header>

    <main class="mx-auto max-w-2xl px-8 py-8 space-y-6">
      <form class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
        <div>
          <label class="block text-sm font-medium text-gray-700">Name</label>
          <input
            v-model="form.name"
            type="text"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none"
            required
          >
          <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">
            {{ form.errors.name }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Slug</label>
          <input
            v-model="form.slug"
            type="text"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-sm focus:border-violet-500 focus:outline-none"
            required
          >
          <p v-if="form.errors.slug" class="mt-1 text-xs text-red-500">
            {{ form.errors.slug }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Description</label>
          <textarea
            v-model="form.description"
            rows="3"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Visibility</label>
          <select
            v-model="form.visibility"
            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-violet-500 focus:outline-none"
          >
            <option value="public">
              Public — anyone can read
            </option>
            <option value="private">
              Private — auth required to read
            </option>
            <option value="secret">
              Secret — invite-only, not listed
            </option>
          </select>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <Link href="/admin/spaces" class="rounded-lg border border-gray-200 px-4 py-2 text-sm hover:bg-gray-50">
            Cancel
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="rounded-lg bg-[#7C4DFF] px-4 py-2 text-sm font-medium text-white hover:bg-[#9B6FFF] disabled:opacity-50"
          >
            Save Changes
          </button>
        </div>
      </form>

      <!-- Danger zone -->
      <div class="rounded-xl border border-red-200 bg-white p-6 shadow-sm">
        <h2 class="mb-1 text-sm font-semibold text-red-700">
          Danger Zone
        </h2>
        <p class="mb-4 text-sm text-gray-500">
          Archiving removes this space from all listings. Existing pages are preserved.
        </p>
        <button
          type="button"
          class="rounded-lg border border-red-300 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
          @click="archiveSpace"
        >
          Archive Space
        </button>
      </div>
    </main>
  </div>
</template>
