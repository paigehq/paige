<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import ConfirmModal from '@/components/ConfirmModal.vue'

interface SpaceEntry {
  id: number
  name: string
  slug: string
  action: string
}

interface UserDetail {
  id: number
  name: string
  email: string
  plan: string
  role: string | null
  last_active_at: string | null
  deactivated_at: string | null
  created_at: string
}

const { user, spaces } = defineProps<{ user: UserDetail, spaces: SpaceEntry[] }>()

const roleForm = useForm({ role: user.role ?? '' })
const showDeactivateConfirm = ref(false)

function submitRole(): void {
  roleForm.patch(`/admin/users/${user.id}/role`)
}

function confirmDeactivate(): void {
  router.delete(`/admin/users/${user.id}`)
  showDeactivateConfirm.value = false
}
</script>

<template>
  <div class="min-h-screen bg-[#F7F5FF]">
    <header class="border-b border-gray-200 bg-white px-8 py-4">
      <div class="mx-auto flex max-w-3xl items-center justify-between">
        <div>
          <a href="/admin/users" class="text-sm text-violet-700 hover:underline">← All Users</a>
          <h1 class="mt-1 text-xl font-bold text-[#1A0B3B]">
            {{ user.name }}
          </h1>
        </div>
        <span
          v-if="user.deactivated_at"
          class="rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-600"
        >
          Deactivated
        </span>
      </div>
    </header>

    <main class="mx-auto max-w-3xl px-8 py-8 space-y-8">
      <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">
          Account
        </h2>
        <dl class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-gray-500">
              Email
            </dt>
            <dd class="font-medium text-[#1A0B3B]">
              {{ user.email }}
            </dd>
          </div>
          <div>
            <dt class="text-gray-500">
              Plan
            </dt>
            <dd class="font-medium text-[#1A0B3B] capitalize">
              {{ user.plan }}
            </dd>
          </div>
          <div>
            <dt class="text-gray-500">
              Last Active
            </dt>
            <dd class="text-[#1A0B3B]">
              {{ user.last_active_at ?? 'Never' }}
            </dd>
          </div>
          <div>
            <dt class="text-gray-500">
              Member Since
            </dt>
            <dd class="text-[#1A0B3B]">
              {{ user.created_at }}
            </dd>
          </div>
        </dl>
      </section>

      <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">
          Application Role
        </h2>
        <form class="flex items-end gap-4" @submit.prevent="submitRole">
          <div class="flex-1">
            <select
              v-model="roleForm.role"
              class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400"
            >
              <option value="viewer">
                Viewer
              </option>
              <option value="editor">
                Editor
              </option>
              <option value="admin">
                Admin
              </option>
            </select>
          </div>
          <button
            type="submit"
            :disabled="roleForm.processing"
            class="rounded-lg bg-[#7C4DFF] px-4 py-2 text-sm font-medium text-white hover:bg-[#9B6FFF] disabled:opacity-50"
          >
            Update Role
          </button>
        </form>
      </section>

      <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500">
          Space Access ({{ spaces.length }})
        </h2>
        <p v-if="spaces.length === 0" class="text-sm text-gray-500">
          No explicit space permissions.
        </p>
        <ul v-else class="divide-y divide-gray-100 text-sm">
          <li v-for="space in spaces" :key="space.id" class="flex items-center justify-between py-2">
            <span class="font-medium text-[#1A0B3B]">{{ space.name }}</span>
            <span class="rounded-full bg-[#EDE7FF] px-2 py-0.5 text-xs font-medium text-violet-700">
              {{ space.action }}
            </span>
          </li>
        </ul>
      </section>

      <section v-if="!user.deactivated_at" class="rounded-xl border border-red-200 bg-white p-6 shadow-sm">
        <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-red-500">
          Danger Zone
        </h2>
        <p class="mb-4 text-sm text-gray-500">
          Deactivating this user will revoke all sessions and space access. Their pages are preserved.
        </p>
        <button
          type="button"
          class="rounded-lg border border-red-400 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
          @click="showDeactivateConfirm = true"
        >
          Deactivate User
        </button>
      </section>
    </main>

    <ConfirmModal
      v-if="showDeactivateConfirm"
      title="Deactivate this user?"
      message="All their sessions will be revoked and space access removed. This can be reversed by an admin."
      confirm-label="Deactivate"
      :dangerous="true"
      @confirm="confirmDeactivate"
      @cancel="showDeactivateConfirm = false"
    />
  </div>
</template>
